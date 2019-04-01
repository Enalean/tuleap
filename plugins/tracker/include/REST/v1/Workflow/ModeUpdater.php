<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Tracker;
use Transition;
use TransitionFactory;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use Workflow;
use Workflow_Dao;

class ModeUpdater
{
    /**
     * @var Workflow_Dao
     */
    private $workflow_dao;

    /**
     * @var TransitionReplicator
     */
    private $transition_replicator;
    /**
     * @var TransitionFactory
     */
    private $transition_factory;
    /**
     * @var TransitionRetriever
     */
    private $transition_retriever;

    public function __construct(
        Workflow_Dao $workflow_dao,
        TransitionFactory $transition_factory,
        TransitionRetriever $transition_retriever,
        TransitionReplicator $transition_replicator
    ) {
        $this->workflow_dao          = $workflow_dao;
        $this->transition_replicator = $transition_replicator;
        $this->transition_factory    = $transition_factory;
        $this->transition_retriever  = $transition_retriever;
    }

    public function switchWorkflowToAdvancedMode(Tracker $tracker) : void
    {
        $workflow    = $tracker->getWorkflow();
        $workflow_id = $workflow->getId();

        if ($workflow->isAdvanced()) {
            return;
        }

        $this->workflow_dao->switchWorkflowToAdvancedMode($workflow_id);
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     * @throws \Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException
     */
    public function switchWorkflowToSimpleMode(Tracker $tracker) : void
    {
        $workflow    = $tracker->getWorkflow();
        $workflow_id = $workflow->getId();

        if (! $workflow->isAdvanced()) {
            return;
        }

        foreach ($this->getTransitionsOrderedByTo($workflow) as $transitions_for_a_state) {
            $this->replicatePerState($transitions_for_a_state);
        }

        $this->workflow_dao->switchWorkflowToSimpleMode($workflow_id);
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     * @throws \Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException
     */
    private function replicatePerState(array $transitions_for_a_state)
    {
        $first_transition = $this->getFirstTransition($transitions_for_a_state[0]);

        foreach ($transitions_for_a_state as $transition) {
            if ($first_transition->getId() === $transition->getId()) {
                continue;
            }

            $this->transition_replicator->replicate($first_transition, $transition);
        }
    }

    /**
     * @return Transition
     */
    private function getFirstTransition(Transition $first_transition_in_array) : Transition
    {
        try {
            return $this->transition_retriever->getFirstSiblingTransition($first_transition_in_array);
        } catch (NoSiblingTransitionException $exception) {
            return $first_transition_in_array;
        }
    }

    /**
     * @return Transition[]
     */
    private function getTransitionsOrderedByTo(Workflow $workflow) : array
    {
        $all_transitions         = $this->transition_factory->getTransitions($workflow);
        $all_ordered_transitions = [];

        foreach ($all_transitions as $transition) {
            $to_id = $transition->getIdTo();

            $all_ordered_transitions[$to_id][] = $transition;
        }

        return $all_ordered_transitions;
    }
}
