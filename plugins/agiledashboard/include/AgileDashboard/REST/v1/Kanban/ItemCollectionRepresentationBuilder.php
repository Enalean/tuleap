<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanItemDao;
use Tracker_ArtifactFactory;
use PFUser;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;

class ItemCollectionRepresentationBuilder
{
    /** @var AgileDashboard_KanbanItemDao */
    private $kanban_item_dao;
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;
    /** @var ItemRepresentationBuilder */
    private $item_representation_builder;

    public function __construct(
        AgileDashboard_KanbanItemDao $kanban_item_dao,
        Tracker_ArtifactFactory $artifact_factory,
        ItemRepresentationBuilder $item_representation_builder
    ) {
        $this->kanban_item_dao             = $kanban_item_dao;
        $this->artifact_factory            = $artifact_factory;
        $this->item_representation_builder = $item_representation_builder;
    }

    public function build(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        AgileDashboard_Kanban $kanban,
        $limit,
        $offset
    ) {
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
                $column_identifier->getColumnId(),
                $limit,
                $offset
            );
        }

        $total_size = (int) $this->kanban_item_dao->foundRows();
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
