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

use Docman_ItemDao;
use Docman_PermissionsManager;
use Tuleap\Docman\REST\v1\ItemRepresentationCollectionBuilder;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

final class BuildSearchedItemRepresentationsFromSearchReport
{
    public function __construct(
        private Docman_ItemDao $item_dao,
        private ItemStatusMapper $status_mapper,
        private \UserManager $user_manager,
        private Docman_PermissionsManager $permissions_manager,
        private ItemRepresentationCollectionBuilder $item_representation_collection_builder,
        private \Docman_ItemFactory $item_factory,
    ) {
    }

    public function build(\Docman_Report $report, \Docman_Item $folder, \PFUser $user, int $limit, int $offset): SearchRepresentationsCollection
    {
        $results = $this->item_dao->searchByGroupId($folder->getGroupId(), $report, ['limit' => $limit, 'offset' => $offset]);

        $search_results = [];
        foreach ($results as $row) {
            if (! $this->permissions_manager->userCanRead($user, $row['item_id'])) {
                continue;
            }
            $item = $this->item_factory->getItemFromRow($row);
            if (! $item) {
                continue;
            }
            $owner = $this->user_manager->getUserById($row['user_id']);
            assert($owner instanceof \PFUser);
            $search_results[] = SearchRepresentation::build(
                $item,
                $this->status_mapper->getItemStatusFromItemStatusNumber((int) $item->getStatus()),
                $owner,
                $this->item_representation_collection_builder->buildParentRowCollection($item, $user, $limit, $offset)
            );
        }

        $whole_collection = $this->item_dao->searchByGroupId($folder->getGroupId(), $report, ['only_count' => true]);

        return new SearchRepresentationsCollection($search_results, (int) $whole_collection->getRow()["total"]);
    }
}
