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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\ExplicitBacklog;

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

    public function getTopBacklogItemsForProject(int $project_id, int $limit, int $offset)
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS artifact_id
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                WHERE project_id = ?
                LIMIT ?
                OFFSET ?";

        return $this->getDB()->run($sql, $project_id, $limit, $offset);
    }

    public function removeArtifactFromExplicitBacklog(int $artifact_id): void
    {
        $sql = 'DELETE FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                WHERE artifact_id = ?';

        $this->getDB()->run($sql, $artifact_id);
    }

    public function removeExplicitBacklogOfProject(int $project_id): void
    {
        $sql = 'DELETE plugin_agiledashboard_planning_artifacts_explicit_backlog, plugin_agiledashboard_planning_explicit_backlog_usage
                FROM plugin_agiledashboard_planning_artifacts_explicit_backlog
                INNER JOIN plugin_agiledashboard_planning_explicit_backlog_usage
                    ON (plugin_agiledashboard_planning_artifacts_explicit_backlog.project_id = plugin_agiledashboard_planning_explicit_backlog_usage.project_id)
                WHERE plugin_agiledashboard_planning_artifacts_explicit_backlog.project_id = ?';

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
}
