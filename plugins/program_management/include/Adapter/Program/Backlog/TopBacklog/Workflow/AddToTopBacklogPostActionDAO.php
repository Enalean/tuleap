<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CreatePostAction;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\DeletePostAction;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\SearchByTransitionId;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\SearchByWorkflow;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Workflow\WorkflowIdentifier;

final class AddToTopBacklogPostActionDAO extends DataAccessObject implements SearchByTransitionId, SearchByWorkflow, DeletePostAction, CreatePostAction
{
    /**
     * @psalm-return list<array{id: int, transition_id: int}>
     */
    #[\Override]
    public function searchByWorkflowId(WorkflowIdentifier $workflow_identifier): array
    {
        $sql = 'SELECT plugin_program_management_workflow_action_add_top_backlog.id, plugin_program_management_workflow_action_add_top_backlog.transition_id
            FROM plugin_program_management_workflow_action_add_top_backlog
            JOIN tracker_workflow_transition ON (tracker_workflow_transition.transition_id = plugin_program_management_workflow_action_add_top_backlog.transition_id)
            WHERE tracker_workflow_transition.workflow_id = ?';

        return $this->getDB()->run($sql, $workflow_identifier->getId());
    }

    /**
     * @return array{id: int}|null
     */
    #[\Override]
    public function searchByTransitionID(int $transition_id): ?array
    {
        $sql = 'SELECT id
            FROM plugin_program_management_workflow_action_add_top_backlog
            WHERE transition_id = ?';

        return $this->getDB()->row($sql, $transition_id);
    }

    #[\Override]
    public function createPostActionForTransitionID(int $transition_id): void
    {
        $this->getDB()->insert(
            'plugin_program_management_workflow_action_add_top_backlog',
            ['transition_id' => $transition_id]
        );
    }

    #[\Override]
    public function deleteTransitionPostActions(int $transition_id): void
    {
        $sql = 'DELETE
                FROM plugin_program_management_workflow_action_add_top_backlog
                WHERE plugin_program_management_workflow_action_add_top_backlog.transition_id = ?';

        $this->getDB()->run(
            $sql,
            $transition_id
        );
    }

    public function deleteWorkflowPostActions(int $workflow_id): void
    {
        $sql = 'DELETE plugin_program_management_workflow_action_add_top_backlog.*
                FROM plugin_program_management_workflow_action_add_top_backlog
                JOIN tracker_workflow_transition ON (plugin_program_management_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                JOIN tracker_workflow ON (tracker_workflow.workflow_id = tracker_workflow_transition.workflow_id)
                WHERE tracker_workflow.workflow_id = ?';

        $this->getDB()->run(
            $sql,
            $workflow_id
        );
    }
}
