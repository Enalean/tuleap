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

use Tracker_FormElement_Field;
use Transition;
use Transition_PostAction;
use Transition_PostActionSubFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Workflow;

class AddToTopBacklogPostActionFactory implements Transition_PostActionSubFactory
{
    /**
     * @var array<int, array<int, int>>
     */
    private $cache = [];
    /**
     * @var AddToTopBacklogPostActionDAO
     */
    private $add_to_top_backlog_post_action_dao;
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;

    public function __construct(
        AddToTopBacklogPostActionDAO $add_to_top_backlog_post_action_dao,
        BuildProgram $build_program,
        TopBacklogChangeProcessor $top_backlog_change_processor
    ) {
        $this->add_to_top_backlog_post_action_dao = $add_to_top_backlog_post_action_dao;
        $this->build_program                      = $build_program;
        $this->top_backlog_change_processor       = $top_backlog_change_processor;
    }

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $workflow_id = (int) $workflow->getId();
        if (isset($this->cache[$workflow_id])) {
            return;
        }

        foreach ($this->add_to_top_backlog_post_action_dao->searchByWorkflow($workflow) as $row) {
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
                        $this->build_program,
                        $this->top_backlog_change_processor
                    )
                ];
            }
            return [];
        }

        $project_id = (int) $transition->getGroupId();
        try {
            ProgramIdentifier::fromId($this->build_program, $project_id, new AddToBacklogPostActionAllPowerfulUser());
        } catch (ProgramAccessException | ProjectIsNotAProgramException $e) {
            return [];
        }

        $row = $this->add_to_top_backlog_post_action_dao->searchByTransitionId((int) $transition->getId());
        if ($row !== null) {
            return [
                new AddToTopBacklogPostAction(
                    $transition,
                    $row['id'],
                    $this->build_program,
                    $this->top_backlog_change_processor
                )
            ];
        }

        return [];
    }

    public function saveObject(Transition_PostAction $post_action): void
    {
        $to_transition_id = (int) $post_action->getTransition()->getId();

        $this->add_to_top_backlog_post_action_dao->createPostActionForTransitionID(
            $to_transition_id
        );
    }

    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field): bool
    {
        return false;
    }

    public function duplicate(Transition $from_transition, int $to_transition_id, array $field_mapping): void
    {
        $postactions = $this->loadPostActions($from_transition);
        if (count($postactions) > 0) {
            $this->add_to_top_backlog_post_action_dao->createPostActionForTransitionID(
                $to_transition_id
            );
        }
    }

    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition): void
    {
        throw new \LogicException('XML import/export of program management add to top backlog post action is not implemented');
    }
}
