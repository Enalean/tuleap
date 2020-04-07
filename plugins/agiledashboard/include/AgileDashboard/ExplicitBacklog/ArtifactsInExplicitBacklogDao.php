<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class ArtifactsInExplicitBacklogDao extends DataAccessObject
{
    public function addArtifactToProjectBacklog(int $project_id, int $artifact_id): void
    {
        $sql = 'INSERT INTO plugin_agiledashboard_planning_artifacts_explicit_backlog (project_id, artifact_id)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE project_id=?, artifact_id=?';

        $this->getDB()->run($sql, $project_id, $artifact_id, $project_id, $artifact_id);
    }

    public function getTopBacklogItemsForProjectSortedByRank(int $project_id, int $limit, int $offset)
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                INNER JOIN tracker_artifact_priority_rank
                    ON plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id = tracker_artifact_priority_rank.artifact_id
                WHERE project_id = ?
                ORDER BY tracker_artifact_priority_rank.rank
                LIMIT ?
                OFFSET ?";

        return $this->getDB()->run($sql, $project_id, $limit, $offset);
    }

    public function getAllTopBacklogItemsForProjectSortedByRank(int $project_id)
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                INNER JOIN tracker_artifact_priority_rank
                    ON plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id = tracker_artifact_priority_rank.artifact_id
                WHERE project_id = ?
                ORDER BY tracker_artifact_priority_rank.rank";

        return $this->getDB()->run($sql, $project_id);
    }

    public function getAllArtifactNotInTopBacklogInTracker(int $tracker_id)
    {
        $sql = "SELECT tracker_artifact.id as artifact_id
                FROM tracker_artifact
                LEFT JOIN plugin_agiledashboard_planning_artifacts_explicit_backlog
                    ON (tracker_artifact.id = plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id)
                WHERE tracker_artifact.tracker_id = ?
                AND plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id IS NULL";

        return $this->getDB()->run($sql, $tracker_id);
    }

    public function removeArtifactFromExplicitBacklog(int $artifact_id): void
    {
        $sql = 'DELETE FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                WHERE artifact_id = ?';

        $this->getDB()->run($sql, $artifact_id);
    }

    public function removeItemsFromExplicitBacklogOfProject(int $project_id, array $to_remove_ids): void
    {
        $artifact_ids_in_condition = EasyStatement::open()->in('?*', $to_remove_ids);

        $sql = "DELETE FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                WHERE project_id = ?
                AND artifact_id IN ($artifact_ids_in_condition)";

        $parameters = array_merge([$project_id], $artifact_ids_in_condition->values());
        $this->getDB()->safeQuery($sql, $parameters);
    }

    public function removeExplicitBacklogOfProject(int $project_id): void
    {
        $sql = 'DELETE plugin_agiledashboard_planning_artifacts_explicit_backlog, plugin_agiledashboard_planning_explicit_backlog_usage
                FROM plugin_agiledashboard_planning_explicit_backlog_usage
                LEFT JOIN plugin_agiledashboard_planning_artifacts_explicit_backlog
                    ON (plugin_agiledashboard_planning_artifacts_explicit_backlog.project_id = plugin_agiledashboard_planning_explicit_backlog_usage.project_id)
                WHERE plugin_agiledashboard_planning_explicit_backlog_usage.project_id = ?';

        $this->getDB()->run($sql, $project_id);
    }

    public function removeExplicitBacklogOfPlanning(int $planning_id): void
    {
        $sql = 'DELETE plugin_agiledashboard_planning_artifacts_explicit_backlog
                FROM plugin_agiledashboard_planning
                INNER JOIN plugin_agiledashboard_planning_artifacts_explicit_backlog
                    ON plugin_agiledashboard_planning_artifacts_explicit_backlog.project_id = plugin_agiledashboard_planning.group_id
                WHERE plugin_agiledashboard_planning.id =?';

        $this->getDB()->run($sql, $planning_id);
    }

    public function removeNoMoreSelectableItemsFromExplicitBacklogOfProject(
        array $planning_tracker_ids,
        int $project_id
    ): void {
        $where_condition = EasyStatement::open()
            ->in('tracker_artifact.tracker_id NOT IN (?*)', $planning_tracker_ids)
            ->andWith('plugin_agiledashboard_planning_artifacts_explicit_backlog.project_id = ?');

        $sql = "DELETE plugin_agiledashboard_planning_artifacts_explicit_backlog.*
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                    INNER JOIN tracker_artifact
                        ON (plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id = tracker_artifact.id)
                WHERE $where_condition";

        $this->getDB()->safeQuery($sql, array_merge($where_condition->values(), [$project_id]));
    }

    public function isArtifactInTopBacklogOfProject(int $artifact_id, int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                WHERE artifact_id = ?
                    AND project_id = ?";

        $rows = $this->getDB()->run($sql, $artifact_id, $project_id);

        return count($rows) > 0;
    }

    public function getNumberOfItemsInExplicitBacklog(int $project_id): int
    {
        $sql = "SELECT count(*)
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                WHERE project_id = ?";

        return $this->getDB()->single($sql, [$project_id]);
    }

    public function cleanUpDirectlyPlannedItemsInArtifact(
        int $milestone_artifact_id,
        array $linked_artifact_ids
    ) {
        $where_condition = EasyStatement::open()
            ->in('plugin_agiledashboard_planning_artifacts_explicit_backlog.artifact_id IN (?*)', $linked_artifact_ids)
            ->andWith('tracker_artifact.id = ?');

        $sql = "DELETE plugin_agiledashboard_planning_artifacts_explicit_backlog.*
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                    INNER JOIN tracker
                        ON (tracker.group_id = plugin_agiledashboard_planning_artifacts_explicit_backlog.project_id)
                    INNER JOIN tracker_artifact
                        ON (tracker_artifact.tracker_id = tracker.id)
                WHERE $where_condition";

        $this->getDB()->safeQuery($sql, array_merge($where_condition->values(), [$milestone_artifact_id]));
    }
}
