<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use AgileDashboard_KanbanItemManager;
use Tracker_Artifact;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;

class ItemRepresentationBuilder
{
    /**
     * @var AgileDashboard_KanbanItemManager
     */
    private $kanban_item_manager;
    /**
     * @var TimeInfoFactory
     */
    private $time_info_factory;

    public function __construct(AgileDashboard_KanbanItemManager $kanban_item_manager, TimeInfoFactory $time_info_factory)
    {
        $this->kanban_item_manager = $kanban_item_manager;
        $this->time_info_factory   = $time_info_factory;
    }

    public function buildItemRepresentation(Tracker_Artifact $artifact)
    {
        $item_representation = new KanbanItemRepresentation();

        $item_in_backlog = $this->kanban_item_manager->isKanbanItemInBacklog($artifact);
        $in_column       = ($item_in_backlog) ? ColumnIdentifier::BACKLOG_COLUMN : null;

        if (! $in_column) {
            $item_in_archive = $this->kanban_item_manager->isKanbanItemInArchive($artifact);
            $in_column = ($item_in_archive) ? ColumnIdentifier::ARCHIVE_COLUMN : null;
        }

        if (! $in_column) {
            $in_column = $this->kanban_item_manager->getColumnIdOfKanbanItem($artifact);
        }

        $item_representation->build(
            $artifact,
            $this->time_info_factory->getTimeInfo($artifact),
            $in_column
        );

        return $item_representation;
    }
}
