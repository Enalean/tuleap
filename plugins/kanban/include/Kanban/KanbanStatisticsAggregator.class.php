<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use EventManager;

final class KanbanStatisticsAggregator
{
    private const CARD_DRAG_AND_DROP     = 'ad_kanban_card_drag_drop';
    private const EXPAND_COLLAPSE_COLUMN = 'ad_kanban_expand_collapse_column';
    private const WIP_MODIFICATION       = 'ad_kanban_wip_modification';
    private const KANBAN_RENAMING        = 'ad_kanban_renaming';
    private const KANBAN_ADD_IN_PLACE    = 'ad_kanban_add_in_place';

    public function __construct(private readonly EventManager $event_manager)
    {
    }

    private function addHit(int $project_id, string $type): void
    {
        $params = [
            'project_id' => $project_id,
            'statistic_name' => $type,
        ];
        $this->event_manager->processEvent('aggregate_statistics', $params);
    }

    public function addCardDragAndDropHit(int $project_id): void
    {
        $this->addHit($project_id, self::CARD_DRAG_AND_DROP);
    }

    public function addExpandCollapseColumnHit(int $project_id): void
    {
        $this->addHit($project_id, self::EXPAND_COLLAPSE_COLUMN);
    }

    public function addWIPModificationHit(int $project_id): void
    {
        $this->addHit($project_id, self::WIP_MODIFICATION);
    }

    public function addKanbanRenamingHit(int $project_id): void
    {
        $this->addHit($project_id, self::KANBAN_RENAMING);
    }

    public function addKanbanAddInPlaceHit(int $project_id): void
    {
        $this->addHit($project_id, self::KANBAN_ADD_IN_PLACE);
    }

    /**
     * @return array<string, string>
     */
    public function getStatisticsLabels(): array
    {
        return [
            self::CARD_DRAG_AND_DROP     => 'Kanban card drag & drop',
            self::EXPAND_COLLAPSE_COLUMN => 'Kanban expanded or collapsed column',
            self::WIP_MODIFICATION       => 'Kanban WIP modification',
            self::KANBAN_RENAMING        => 'Kanban renaming',
            self::KANBAN_ADD_IN_PLACE    => 'Kanban add in place',
        ];
    }

    public function getStatistics(string $statistic_name, string $date_start, string $date_end): mixed
    {
        $statistics_data = [];
        $params          = [
            'statistic_name' => $statistic_name,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'result' => &$statistics_data,
        ];
        $this->event_manager->processEvent('get_statistics_aggregation', $params);

        return $statistics_data;
    }
}
