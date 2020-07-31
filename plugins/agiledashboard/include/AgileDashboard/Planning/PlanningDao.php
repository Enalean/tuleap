<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use PlanningParameters;
use TrackerDao;
use Tuleap\DB\DataAccessObject;

class PlanningDao extends DataAccessObject
{
    /**
     * @var TrackerDao
     */
    private $tracker_dao;

    public function __construct(?TrackerDao $tracker_dao = null)
    {
        parent::__construct();
        $this->tracker_dao = $tracker_dao ?? new TrackerDao();
    }

    public function createPlanning(int $group_id, PlanningParameters $planning_parameters): int
    {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($group_id, $planning_parameters): int {
                $planning_id = (int) $db->insertReturnId(
                    'plugin_agiledashboard_planning',
                    [
                        'name'                => $planning_parameters->name,
                        'group_id'            => $group_id,
                        'planning_tracker_id' => $planning_parameters->planning_tracker_id,
                        'backlog_title'       => $planning_parameters->backlog_title,
                        'plan_title'          => $planning_parameters->plan_title,
                    ]
                );

                $this->createBacklogTrackers($db, $planning_id, $planning_parameters);
                return $planning_id;
            }
        );
    }

    private function createBacklogTracker(EasyDB $db, int $planning_id, int $backlog_tracker_id): void
    {
        $db->insert(
            'plugin_agiledashboard_planning_backlog_tracker',
            [
                'planning_id' => $planning_id,
                'tracker_id'  => $backlog_tracker_id
            ]
        );
    }

    /**
     * @psalm-return list<array{id:int, name:string, group_id:int, planning_tracker_id:int, backlog_title:string, plan_title:string}>
     */
    public function searchByProjectId(int $project_id): array
    {
        $sql = 'SELECT * FROM plugin_agiledashboard_planning WHERE group_id = ?';
        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @psalm-return array{id:int, name:string, group_id:int, planning_tracker_id:int, backlog_title:string, plan_title:string}
     */
    public function searchById(int $planning_id): ?array
    {
        $sql = 'SELECT * FROM plugin_agiledashboard_planning WHERE id = ?';
        return $this->getDB()->row($sql, $planning_id);
    }

    /**
     * @psalm-return array{id:int, name:string, group_id:int, planning_tracker_id:int, backlog_title:string, plan_title:string}
     */
    public function searchByMilestoneTrackerId(int $milestone_tracker_id): ?array
    {
        $sql = 'SELECT * FROM plugin_agiledashboard_planning WHERE planning_tracker_id = ?';
        return $this->getDB()->row($sql, $milestone_tracker_id);
    }

    /**
     * @psalm-param  list<int> $milestone_tracker_ids
     * @psalm-return list<array{id:int, name:string, group_id:int, planning_tracker_id:int, backlog_title:string, plan_title:string}>
     */
    public function searchByMilestoneTrackerIds(array $milestone_tracker_ids): array
    {
        $in_statement = EasyStatement::open()->in('planning_tracker_id IN (?*)', $milestone_tracker_ids);
        $sql          = "SELECT * FROM plugin_agiledashboard_planning WHERE $in_statement";
        return $this->getDB()->safeQuery($sql, $in_statement->values());
    }

    /**
     * @psalm-return list<array{id:int, name:string, group_id:int, planning_tracker_id:int, backlog_title:string, plan_title:string, backlog_tracker_id:int}>
     */
    public function searchByBacklogTrackerId(int $backlog_tracker_id): array
    {
        $sql = 'SELECT planning.*,
                    backlog_trackers.tracker_id AS backlog_tracker_id
                FROM plugin_agiledashboard_planning AS planning
                    INNER JOIN plugin_agiledashboard_planning_backlog_tracker AS backlog_trackers
                ON planning.id = backlog_trackers.planning_id
                WHERE backlog_trackers.tracker_id = ?
                GROUP BY planning.id';
        return $this->getDB()->run($sql, $backlog_tracker_id);
    }

    /**
     * @psalm-return list<array{planning_id:int, tracker_id:int}>
     */
    public function searchBacklogTrackersByPlanningId(int $planning_id): array
    {
        $sql = 'SELECT * FROM plugin_agiledashboard_planning_backlog_tracker WHERE planning_id = ?';
        return $this->getDB()->run($sql, $planning_id);
    }

    /**
     * @psalm-return list<array{planning_id:int, tracker_id:int}>
     */
    public function searchBacklogTrackersByTrackerId(int $tracker_id): array
    {
        $sql = 'SELECT * FROM plugin_agiledashboard_planning_backlog_tracker WHERE tracker_id = ?';
        return $this->getDB()->run($sql, $tracker_id);
    }

    /**
     * @psalm-return list<int>
     */
    public function searchBacklogTrackerIdsByProjectId(int $project_id): array
    {
        $sql  = 'SELECT tracker_id
                FROM plugin_agiledashboard_planning_backlog_tracker
                    JOIN plugin_agiledashboard_planning
                ON (plugin_agiledashboard_planning_backlog_tracker.planning_id = plugin_agiledashboard_planning.id)
                WHERE plugin_agiledashboard_planning.group_id = ?';
        $rows = $this->getDB()->run($sql, $project_id);
        $ids  = [];
        foreach ($rows as $row) {
            $ids[] = $row['tracker_id'];
        }
        return $ids;
    }

    /**
     * @psalm-return list<int>
     */
    public function searchMilestoneTrackerIdsByProjectId(int $project_id): array
    {
        $sql  = 'SELECT planning_tracker_id FROM plugin_agiledashboard_planning WHERE group_id = ?';
        $rows = $this->getDB()->run($sql, $project_id);
        $ids  = [];
        foreach ($rows as $row) {
            $ids[] = $row['planning_tracker_id'];
        }
        return $ids;
    }

    /**
     * @return \DataAccessResult|false
     */
    public function searchNonPlanningTrackersByGroupId(int $project_id)
    {
        $planning_tracker_ids = $this->searchMilestoneTrackerIdsByProjectId($project_id);
        return $this->tracker_dao->searchByGroupIdWithExcludedIds($project_id, $planning_tracker_ids);
    }

    public function updatePlanning(int $planning_id, PlanningParameters $planning_parameters): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($planning_id, $planning_parameters) {
                $db->update(
                    'plugin_agiledashboard_planning',
                    [
                        'name'                => $planning_parameters->name,
                        'planning_tracker_id' => $planning_parameters->planning_tracker_id,
                        'backlog_title'       => $planning_parameters->backlog_title,
                        'plan_title'          => $planning_parameters->plan_title,
                    ],
                    ['id' => $planning_id]
                );
                $this->updateBacklogTrackers($db, $planning_id, $planning_parameters);
            }
        );
    }

    private function updateBacklogTrackers(EasyDB $db, int $planning_id, PlanningParameters $planning_parameters): void
    {
        $this->deletePlanningBacklogTrackers($db, $planning_id);
        $this->createBacklogTrackers($db, $planning_id, $planning_parameters);
    }

    private function createBacklogTrackers(EasyDB $db, int $planning_id, PlanningParameters $planning_parameters): void
    {
        foreach ($planning_parameters->backlog_tracker_ids as $backlog_tracker_id) {
            $this->createBacklogTracker($db, $planning_id, (int) $backlog_tracker_id);
        }
    }

    public function deletePlanning(int $planning_id): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($planning_id) {
                $sql = 'DELETE FROM plugin_agiledashboard_planning WHERE id = ?';
                $db->run($sql, $planning_id);

                $this->deletePlanningBacklogTrackers($db, $planning_id);
            }
        );
    }

    private function deletePlanningBacklogTrackers(EasyDB $db, int $planning_id): void
    {
        $db->delete('plugin_agiledashboard_planning_backlog_tracker', ['planning_id' => $planning_id]);
    }
}
