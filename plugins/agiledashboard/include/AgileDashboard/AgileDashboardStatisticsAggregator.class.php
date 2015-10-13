<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboardStatisticsAggregator {
    const CARD_DRAG_AND_DROP     = 'ad_kanban_card_drag_drop';

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct() {
        $this->event_manager = EventManager::instance();
    }

    private function addHit($project_id, $type) {
        $params = array(
            'project_id'     => $project_id,
            'statistic_name' => $type
        );
        $this->event_manager->processEvent('aggregate_statistics', $params);
    }

    public function addCardDragAndDropHit($project_id) {
        $this->addHit($project_id, self::CARD_DRAG_AND_DROP);
    }

    public function getStatisticsLabels() {
        $res[self::CARD_DRAG_AND_DROP] = 'Kanban card drag & drop';

        return $res;
    }

    public function getStatistics($statistic_name, $date_start, $date_end) {
        $statistics_data = array();
        $params = array(
            'statistic_name' => $statistic_name,
            'date_start'     => $date_start,
            'date_end'       => $date_end,
            'result'         => &$statistics_data
        );
        $this->event_manager->processEvent('get_statistics_aggregation', $params);

        return $statistics_data;
    }
}