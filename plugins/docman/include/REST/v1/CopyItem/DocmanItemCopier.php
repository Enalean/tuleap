<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\CopyItem;

use DateTimeImmutable;
use Docman_Folder;
use Docman_ItemFactory;
use Docman_LinkVersionFactory;
use Docman_PermissionsManager;
use EventManager;
use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use RuntimeException;
use Tuleap\Docman\DestinationCloneItem;
use Tuleap\Docman\Metadata\MetadataFactoryBuilder;
use Tuleap\Docman\REST\v1\CreatedItemRepresentation;

final class DocmanItemCopier
{
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var BeforeCopyVisitor
     */
    private $before_copy_visitor;
    /**
     * @var Docman_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var MetadataFactoryBuilder
     */
    private $metadata_factory_builder;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var string
     */
    private $docman_root_path;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var Docman_LinkVersionFactory
     */
    private $link_version_factory;

    public function __construct(
        Docman_ItemFactory $item_factory,
        BeforeCopyVisitor $before_copy_visitor,
        Docman_PermissionsManager $permissions_manager,
        MetadataFactoryBuilder $metadata_factory_builder,
        EventManager $event_manager,
        ProjectManager $project_manager,
        Docman_LinkVersionFactory $link_version_factory,
        string $docman_root_path
    ) {
        $this->item_factory             = $item_factory;
        $this->before_copy_visitor      = $before_copy_visitor;
        $this->permissions_manager      = $permissions_manager;
        $this->metadata_factory_builder = $metadata_factory_builder;
        $this->event_manager            = $event_manager;
        $this->link_version_factory     = $link_version_factory;
        $this->project_manager          = $project_manager;
        $this->docman_root_path         = $docman_root_path;
    }

    public function copyItem(
        DateTimeImmutable $current_time,
        Docman_Folder $destination_folder,
        PFUser $user,
        DocmanCopyItemRepresentation $representation
    ): CreatedItemRepresentation {
        $item_to_copy_id = $representation->item_id;
        $item_to_copy    = $this->item_factory->getItemFromDb($item_to_copy_id);
        if ($item_to_copy === null || ! $this->permissions_manager->userCanAccess($user, $item_to_copy->getId())) {
            throw new RestException(
                404,
                sprintf('The item #%d can not be copied, the item does not exist', $item_to_copy_id)
            );
        }

        if ($destination_folder->getGroupId() !== $item_to_copy->getGroupId()) {
            throw new RestException(
                400,
                'The parent folder and the copied item are not in the same project'
            );
        }

        $copy_expectation = $item_to_copy->accept(
            $this->before_copy_visitor,
            ['destination' => $destination_folder, 'current_time' => $current_time]
        );

        $metadata_mapping = [];
        $this->metadata_factory_builder->getMetadataFactoryForItem($item_to_copy)->getMetadataMapping(
            $destination_folder->getGroupId(),
            $metadata_mapping
        );

        $item_mapping = $this->item_factory->cloneItems(
            $user,
            $metadata_mapping,
            false,
            $this->docman_root_path,
            $item_to_copy,
            DestinationCloneItem::fromNewParentFolder($destination_folder, $this->project_manager, $this->link_version_factory)
        );

        if (! isset($item_mapping[$item_to_copy_id])) {
            throw new RuntimeException(sprintf('Item #%d does not appear to have been copied', $item_to_copy_id));
        }
        $copied_item_id = $item_mapping[$item_to_copy_id];

        $expected_title_for_copy = $copy_expectation->getExpectedTitle();
        if ($item_to_copy->getTitle() !== $expected_title_for_copy) {
            $this->item_factory->update(['id' => $copied_item_id, 'title' => $expected_title_for_copy]);
        }

        $this->event_manager->processEvent('send_notifications');

        $representation = new CreatedItemRepresentation();
        $representation->build($copied_item_id);

        return $representation;
    }
}
