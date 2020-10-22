<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Administration\PlannableItems;

use Tuleap\DB\DataAccessObject;

class PlannableItemsTrackersDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{project_id:int, tracker_ids:string}>
     */
    public function getPlannableItemsTrackerIds(int $program_top_planning_id): array
    {
        $sql = "SELECT tracker.group_id as project_id, GROUP_CONCAT(tracker.id ORDER BY tracker.id ASC) as tracker_ids
                FROM plugin_agiledashboard_planning_backlog_tracker
                    INNER JOIN tracker ON (
                        tracker.id = plugin_agiledashboard_planning_backlog_tracker.tracker_id
                    )
                WHERE plugin_agiledashboard_planning_backlog_tracker.planning_id = ?
                GROUP BY tracker.group_id";

        return $this->getDB()->run($sql, $program_top_planning_id);
    }

    public function deletePlannableItemsTrackerIdsOfAGivenTeamProject(
        int $team_project_id,
        int $program_top_planning_id
    ): void {
        $sql = "DELETE plugin_agiledashboard_planning_backlog_tracker.*
                FROM plugin_agiledashboard_planning_backlog_tracker
                    INNER JOIN tracker ON (
                        tracker.id = plugin_agiledashboard_planning_backlog_tracker.tracker_id
                    )
                    INNER JOIN plugin_agiledashboard_planning ON (
                        plugin_agiledashboard_planning.id = plugin_agiledashboard_planning_backlog_tracker.planning_id
                    )
                WHERE tracker.group_id = ?
                    AND plugin_agiledashboard_planning.id = ?";

        $this->getDB()->run($sql, $team_project_id, $program_top_planning_id);
    }

    /**
     * @param int[] $plannable_items_tracker_ids
     */
    public function addPlannableItemsTrackerIds(int $program_top_planning_id, array $plannable_items_tracker_ids): void
    {
        $data_to_insert = [];
        foreach ($plannable_items_tracker_ids as $plannable_items_tracker_id) {
            $data_to_insert[] = [
                'planning_id' => $program_top_planning_id,
                'tracker_id'  => $plannable_items_tracker_id
            ];
        }

        if (! empty($data_to_insert)) {
            $this->getDB()->insertMany(
                'plugin_agiledashboard_planning_backlog_tracker',
                $data_to_insert
            );
        }
    }
}
