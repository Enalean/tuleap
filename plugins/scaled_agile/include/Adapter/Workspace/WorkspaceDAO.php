<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Workspace;

use Tuleap\DB\DataAccessObject;
use Tuleap\ScaledAgile\Workspace\UnusedComponentCleaner;

final class WorkspaceDAO extends DataAccessObject implements UnusedComponentCleaner
{
    public function dropUnusedComponents(): void
    {
        $sql = 'DELETE
                    plugin_scaled_agile_plan.*,
                    plugin_scaled_agile_can_prioritize_features.*,
                    plugin_scaled_agile_team_projects.*,
                    plugin_scaled_agile_pending_mirrors.*,
                    plugin_scaled_agile_explicit_top_backlog.*
                FROM `groups`
                LEFT JOIN tracker ON (tracker.group_id = `groups`.group_id)
                LEFT JOIN plugin_scaled_agile_plan ON (plugin_scaled_agile_plan.program_increment_tracker_id = tracker.group_id OR plugin_scaled_agile_plan.plannable_tracker_id = tracker.group_id)
                LEFT JOIN plugin_scaled_agile_can_prioritize_features ON (plugin_scaled_agile_can_prioritize_features.program_increment_tracker_id = plugin_scaled_agile_plan.program_increment_tracker_id)
                LEFT JOIN plugin_scaled_agile_team_projects ON (plugin_scaled_agile_team_projects.team_project_id = `groups`.group_id OR plugin_scaled_agile_team_projects.program_project_id = `groups`.group_id)
                LEFT JOIN tracker_artifact ON (tracker_artifact.tracker_id = tracker.id)
                LEFT JOIN plugin_scaled_agile_pending_mirrors ON (plugin_scaled_agile_pending_mirrors.program_artifact_id = tracker_artifact.id)
                LEFT JOIN plugin_scaled_agile_explicit_top_backlog ON (plugin_scaled_agile_explicit_top_backlog.artifact_id = tracker_artifact.id)
                WHERE `groups`.status = "D"';

        $this->getDB()->run($sql);
    }
}
