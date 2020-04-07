<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata;

use Docman_MetadataFactory;
use Docman_MetadataValueFactory;
use Docman_PermissionsManager;
use ReferenceManager;

class MetadataRecursiveUpdator
{
    /**
     * @var Docman_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var Docman_MetadataValueFactory
     */
    private $metadata_value_factory;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Docman_MetadataFactory
     */
    private $metadata_factory;

    public function __construct(
        Docman_MetadataFactory $metadata_factory,
        Docman_PermissionsManager $permissions_manager,
        Docman_MetadataValueFactory $metadata_value_factory,
        ReferenceManager $reference_manager
    ) {
        $this->permissions_manager    = $permissions_manager;
        $this->metadata_value_factory = $metadata_value_factory;
        $this->reference_manager      = $reference_manager;
        $this->metadata_factory       = $metadata_factory;
    }

    /**
     * @throws NoItemToRecurseException
     */
    public function updateRecursiveMetadataOnFolder(
        ItemImpactedByMetadataChangeCollection $collection,
        int $folder_id,
        int $project_id
    ): void {
        $inheritable_metadata_fields = $this->metadata_factory->getInheritableMdLabelArray();
        if (count(array_diff($collection->getFieldsToUpdate(), $inheritable_metadata_fields)) > 0) {
            return;
        }
        if (! $this->permissions_manager->currentUserCanWriteSubItems($folder_id)) {
            return;
        }

        $visitor                    = $this->permissions_manager->getSubItemsWritableVisitor();
        $list_of_elements_to_update = $visitor->getFolderIdList();

        // Remove the first element (parent item) to keep only the children.
        $this->processUpdate($collection, $folder_id, $project_id, $list_of_elements_to_update);
    }

    /**
     * @throws NoItemToRecurseException
     */
    public function updateRecursiveMetadataOnFolderAndItems(
        ItemImpactedByMetadataChangeCollection $collection,
        int $folder_id,
        int $project_id
    ): void {
        $inheritable_metadata_fields = $this->metadata_factory->getInheritableMdLabelArray();
        if (count(array_diff($collection->getFieldsToUpdate(), $inheritable_metadata_fields)) > 0) {
            return;
        }
        if (! $this->permissions_manager->currentUserCanWriteSubItems($folder_id)) {
            return;
        }

        $visitor                    = $this->permissions_manager->getSubItemsWritableVisitor();
        $list_of_elements_to_update = $visitor->getItemIdList();
        $this->processUpdate($collection, $folder_id, $project_id, $list_of_elements_to_update);
    }

    private function updateFolderSubItems(
        ItemImpactedByMetadataChangeCollection $collection,
        int $folder_id,
        array $list_of_elements_to_update
    ): void {
        $this->metadata_value_factory->massUpdateFromRow($folder_id, $collection->getFieldsToUpdate(), $list_of_elements_to_update);
    }

    private function extractCrossRefForCustomProperties(
        int $project_id,
        ItemImpactedByMetadataChangeCollection $collection,
        array $list_of_elements_to_update
    ): void {
        foreach ($collection->getValuesToExtractCrossReferences() as $value) {
            foreach ($list_of_elements_to_update as $curr_item_id) {
                $this->reference_manager->extractCrossRef(
                    $value,
                    $curr_item_id,
                    ReferenceManager::REFERENCE_NATURE_DOCUMENT,
                    $project_id
                );
            }
        }
    }

    private function processUpdate(
        ItemImpactedByMetadataChangeCollection $collection,
        int $folder_id,
        int $project_id,
        array $list_of_elements_to_update
    ): void {
        // Remove the first element (parent item) to keep only the children.
        array_shift($list_of_elements_to_update);
        if (count($list_of_elements_to_update) === 0) {
            throw new NoItemToRecurseException();
        }
        $this->updateFolderSubItems($collection, $folder_id, $list_of_elements_to_update);

        $this->extractCrossRefForCustomProperties($project_id, $collection, $list_of_elements_to_update);
    }
}
