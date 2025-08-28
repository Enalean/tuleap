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

use Transition;
use Transition_PostAction;
use Transition_PostActionSubFactory;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Workflow\WorkflowProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CreatePostAction;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\SearchByTransitionId;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\SearchByWorkflow;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Workflow;

final class AddToTopBacklogPostActionFactory implements Transition_PostActionSubFactory
{
    /**
     * @var array<int, array<int, int>>
     */
    private array $cache = [];

    public function __construct(
        private readonly SearchByTransitionId $add_to_top_backlog_post_action_dao,
        private readonly RetrieveFullProject $project_retriever,
        private readonly ProgramServiceIsEnabledCertifier $program_certifier,
        private readonly TopBacklogChangeProcessor $top_backlog_change_processor,
        private readonly SearchByWorkflow $search_by_workflow,
        private readonly CreatePostAction $create_post_action,
    ) {
    }

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $workflow_id = (int) $workflow->getId();
        if (isset($this->cache[$workflow_id])) {
            return;
        }

        $workflow_identifier = WorkflowProxy::fromWorkflow($workflow);
        foreach ($this->search_by_workflow->searchByWorkflowId($workflow_identifier) as $row) {
            $this->cache[$workflow_id][$row['transition_id']] = $row['id'];
        }
    }

    /**
     * @return AddToTopBacklogPostAction[]
     * @throws \Tuleap\Tracker\Workflow\Transition\OrphanTransitionException
     */
    public function loadPostActions(Transition $transition): array
    {
        $workflow_id = (int) $transition->getWorkflow()->getId();
        if (isset($this->cache[$workflow_id])) {
            $transition_id = (int) $transition->getId();
            if (isset($this->cache[$workflow_id][$transition_id])) {
                return [
                    new AddToTopBacklogPostAction(
                        $transition,
                        $this->cache[$workflow_id][$transition_id],
                        $this->program_certifier,
                        $this->top_backlog_change_processor
                    ),
                ];
            }
            return [];
        }

        $project_id = (int) $transition->getGroupId();
        $project    = $this->project_retriever->getProject($project_id);
        return $this->program_certifier->certifyProgramServiceEnabled($project)
            ->mapOr(function () use ($transition) {
                $row = $this->add_to_top_backlog_post_action_dao->searchByTransitionId((int) $transition->getId());
                if ($row === null) {
                    return [];
                }
                return [
                    new AddToTopBacklogPostAction(
                        $transition,
                        $row['id'],
                        $this->program_certifier,
                        $this->top_backlog_change_processor
                    ),
                ];
            }, []);
    }

    public function saveObject(Transition_PostAction $post_action): void
    {
        $to_transition_id = (int) $post_action->getTransition()->getId();

        $this->create_post_action->createPostActionForTransitionID(
            $to_transition_id
        );
    }

    public function isFieldUsedInPostActions(TrackerField $field): bool
    {
        return false;
    }

    public function duplicate(Transition $from_transition, int $to_transition_id, array $field_mapping): void
    {
        $postactions = $this->loadPostActions($from_transition);
        if (count($postactions) > 0) {
            $this->create_post_action->createPostActionForTransitionID(
                $to_transition_id
            );
        }
    }

    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition): AddToTopBacklogPostAction
    {
        return new AddToTopBacklogPostAction(
            $transition,
            0,
            $this->program_certifier,
            $this->top_backlog_change_processor
        );
    }
}
