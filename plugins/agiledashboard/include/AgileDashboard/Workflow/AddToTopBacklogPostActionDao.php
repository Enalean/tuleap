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

namespace Tuleap\AgileDashboard\Workflow;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class AddToTopBacklogPostActionDao extends DataAccessObject
{
    public function searchByTransitionId(int $transition_id): ?array
    {
        $sql = "SELECT *
                FROM plugin_agiledashboard_tracker_workflow_action_add_top_backlog
                WHERE transition_id = ?";

        return $this->getDB()->row($sql, $transition_id);
    }

    public function getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction(array $tracker_ids)
    {
        $tracker_ids_in_condition = EasyStatement::open()->in('?*', $tracker_ids);

        $sql = "SELECT DISTINCT tracker.name
                FROM plugin_agiledashboard_tracker_workflow_action_add_top_backlog
                    INNER JOIN tracker_workflow_transition ON (plugin_agiledashboard_tracker_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                    INNER JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                    INNER JOIN tracker ON (tracker_workflow.tracker_id = tracker.id)
                WHERE tracker.id IN ($tracker_ids_in_condition)";

        return $this->getDB()->safeQuery(
            $sql,
            $tracker_ids_in_condition->values()
        );
    }

    public function deleteWorkflowPostActions(int $workflow_id)
    {
        $sql = "DELETE plugin_agiledashboard_tracker_workflow_action_add_top_backlog.*
                FROM plugin_agiledashboard_tracker_workflow_action_add_top_backlog
                    INNER JOIN tracker_workflow_transition ON (plugin_agiledashboard_tracker_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                    INNER JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                WHERE tracker_workflow.workflow_id = ?";

        return $this->getDB()->run(
            $sql,
            $workflow_id
        );
    }

    public function deleteTransitionPostActions(int $transition_id): void
    {
        $sql = "DELETE
                FROM plugin_agiledashboard_tracker_workflow_action_add_top_backlog
                WHERE plugin_agiledashboard_tracker_workflow_action_add_top_backlog.transition_id = ?";

        $this->getDB()->run(
            $sql,
            $transition_id
        );
    }

    public function createPostActionForTransitionId(int $transition_id): void
    {
        $this->getDB()->insert(
            "plugin_agiledashboard_tracker_workflow_action_add_top_backlog",
            ["transition_id" => $transition_id]
        );
    }

    public function isAtLeastOnePostActionDefinedInProject(int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_agiledashboard_tracker_workflow_action_add_top_backlog
                    INNER JOIN tracker_workflow_transition ON (plugin_agiledashboard_tracker_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                    INNER JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                    INNER JOIN tracker ON (tracker_workflow.tracker_id = tracker.id)
                WHERE tracker.group_id = ?";

        $rows = $this->getDB()->run($sql, $project_id);

        return count($rows) > 0;
    }

    public function deleteAllPostActionsInProject(int $project_id): void
    {
        $sql = "DELETE plugin_agiledashboard_tracker_workflow_action_add_top_backlog.*
                FROM plugin_agiledashboard_tracker_workflow_action_add_top_backlog
                    INNER JOIN tracker_workflow_transition ON (plugin_agiledashboard_tracker_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                    INNER JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                    INNER JOIN tracker ON (tracker_workflow.tracker_id = tracker.id)
                WHERE tracker.group_id = ?";

        $this->getDB()->run(
            $sql,
            $project_id
        );
    }
}
