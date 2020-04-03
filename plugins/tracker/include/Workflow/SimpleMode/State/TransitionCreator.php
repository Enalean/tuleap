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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Transition;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use Workflow;

class TransitionCreator
{
    /**
     * @var \TransitionFactory
     */
    private $transition_factory;

    /**
     * @var TransitionReplicator
     */
    private $transition_replicator;

    /**
     * @var TransitionExtractor
     */
    private $transition_extractor;

    public function __construct(
        \TransitionFactory $transition_factory,
        TransitionReplicator $conditions_replicator,
        TransitionExtractor $transition_extractor
    ) {
        $this->transition_factory         = $transition_factory;
        $this->transition_replicator      = $conditions_replicator;
        $this->transition_extractor       = $transition_extractor;
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     * @throws \Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException
     */
    public function createTransitionInState(State $state, Workflow $workflow, TransitionCreationParameters $parameters): Transition
    {
        $transition = $this->transition_factory->createAndSaveTransition($workflow, $parameters);

        try {
            $reference_transition = $this->transition_extractor->extractReferenceTransitionFromState($state);
            $this->transition_replicator->replicate($reference_transition, $transition);
        } catch (NoTransitionForStateException $exception) {
            //Do nothing, we are creating the first transition for the state.
        } finally {
            return $transition;
        }
    }
}
