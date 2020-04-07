<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\State;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
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
     * @var FrozenFieldsDao
     */
    private $frozen_fields_dao;

    /**
     * @var StateFactory
     */
    private $state_factory;

    /**
     * @var TransitionExtractor
     */
    private $transition_extractor;

    /**
     * @var HiddenFieldsetsDao
     */
    private $hidden_fieldsets_dao;

    public function __construct(
        Workflow_Dao $workflow_dao,
        TransitionReplicator $transition_replicator,
        FrozenFieldsDao $frozen_fields_dao,
        HiddenFieldsetsDao $hidden_fieldsets_dao,
        StateFactory $state_factory,
        TransitionExtractor $transition_extractor
    ) {
        $this->workflow_dao          = $workflow_dao;
        $this->transition_replicator = $transition_replicator;
        $this->frozen_fields_dao     = $frozen_fields_dao;
        $this->state_factory         = $state_factory;
        $this->transition_extractor  = $transition_extractor;
        $this->hidden_fieldsets_dao  = $hidden_fieldsets_dao;
    }

    public function switchWorkflowToAdvancedMode(Tracker $tracker): void
    {
        $workflow    = $tracker->getWorkflow();
        $workflow_id = (int) $workflow->getId();

        if ($workflow->isAdvanced()) {
            return;
        }

        $this->frozen_fields_dao->deleteAllPostActionsForWorkflow($workflow_id);
        $this->hidden_fieldsets_dao->deleteAllPostActionsForWorkflow($workflow_id);
        $this->workflow_dao->switchWorkflowToAdvancedMode($workflow_id);
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     * @throws \Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException
     */
    public function switchWorkflowToSimpleMode(Tracker $tracker): void
    {
        $workflow    = $tracker->getWorkflow();
        $workflow_id = $workflow->getId();

        if (! $workflow->isAdvanced()) {
            return;
        }

        foreach ($this->state_factory->getAllStatesForWorkflow($workflow) as $state) {
            $this->replicatePerState($state);
        }

        $this->workflow_dao->switchWorkflowToSimpleMode($workflow_id);
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     * @throws \Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException
     */
    private function replicatePerState(State $state)
    {
        $first_transition = $this->transition_extractor->extractReferenceTransitionFromState($state);

        foreach ($this->transition_extractor->extractSiblingTransitionsFromState($state, $first_transition) as $transition) {
            $this->transition_replicator->replicate($first_transition, $transition);
        }
    }
}
