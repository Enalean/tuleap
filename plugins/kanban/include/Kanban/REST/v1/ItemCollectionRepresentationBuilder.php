<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.w
 */

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanItemDao;
use Tracker_ArtifactFactory;
use PFUser;
use Tuleap\Kanban\ColumnIdentifier;

final class ItemCollectionRepresentationBuilder
{
    public function __construct(
        private readonly KanbanItemDao $kanban_item_dao,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly ItemRepresentationBuilder $item_representation_builder,
    ) {
    }

    public function build(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        Kanban $kanban,
        int $limit,
        int $offset,
    ): ItemCollectionRepresentation {
        if ($column_identifier->isBacklog()) {
            $data = $this->kanban_item_dao->searchPaginatedBacklogItemsByTrackerId(
                $kanban->getTrackerId(),
                $limit,
                $offset
            );
        } elseif ($column_identifier->isArchive()) {
            $data = $this->kanban_item_dao->searchPaginatedArchivedItemsByTrackerId(
                $kanban->getTrackerId(),
                $limit,
                $offset
            );
        } else {
            $data = $this->kanban_item_dao->searchPaginatedItemsInColumn(
                $kanban->getTrackerId(),
                (int) $column_identifier->getColumnId(),
                $limit,
                $offset
            );
        }

        $total_size = $this->kanban_item_dao->foundRows();
        $collection = [];
        foreach ($data as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $collection[] = $this->item_representation_builder->buildItemRepresentationInColumn(
                $column_identifier,
                $artifact
            );
        }

        return new ItemCollectionRepresentation($collection, $total_size);
    }
}
