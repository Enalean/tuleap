<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Folders;

use Tuleap\Docman\REST\v1\ItemRepresentationCollectionBuilder;
use Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Search\FilePropertiesVisitor;
use Tuleap\Docman\REST\v1\Search\SearchRepresentationTypeVisitor;

final class BuildSearchedItemRepresentationsFromSearchReport
{
    public function __construct(
        private ItemStatusMapper $status_mapper,
        private \UserManager $user_manager,
        private ItemRepresentationCollectionBuilder $item_representation_collection_builder,
        private \Docman_ItemFactory $item_factory,
        private SearchRepresentationTypeVisitor $type_visitor,
        private FilePropertiesVisitor $file_properties_visitor,
    ) {
    }

    public function build(\Docman_Report $report, \Docman_Item $folder, \PFUser $user, int $limit, int $offset): SearchRepresentationsCollection
    {
        $nb_item_found = 0;
        $results       = $this->item_factory->getItemList(
            $folder->getId(),
            $nb_item_found,
            [
                'api_limit'       => $limit,
                'api_offset'      => $offset,
                'filter'          => $report,
                'user'            => $user,
                'ignore_obsolete' => true,
            ]
        );

        $search_results = [];
        foreach ($results as $item) {
            assert($item instanceof \Docman_Item);

            try {
                $converted_status = $this->status_mapper->getItemStatusFromItemStatusNumber((int) $item->getStatus());
            } catch (HardCodedMetadataException $e) {
                $converted_status = null;
            }

            $owner = $this->user_manager->getUserById($item->getOwnerId());
            assert($owner instanceof \PFUser);
            $search_results[] = SearchRepresentation::build(
                $item,
                \Codendi_HTMLPurifier::instance(),
                $converted_status,
                $owner,
                $this->item_representation_collection_builder->buildParentRowCollection($item, $user, $limit, $offset),
                $item->accept($this->type_visitor),
                $item->accept($this->file_properties_visitor),
            );
        }

        return new SearchRepresentationsCollection($search_results, $nb_item_found);
    }
}
