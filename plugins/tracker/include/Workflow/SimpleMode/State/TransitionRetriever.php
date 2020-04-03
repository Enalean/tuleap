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

use Tracker_Artifact;
use Transition;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class TransitionRetriever
{
    /**
     * @var StateFactory
     */
    private $state_factory;

    /**
     * @var TransitionExtractor
     */
    private $transition_extractor;

    public function __construct(
        StateFactory $state_factory,
        TransitionExtractor $transition_extractor
    ) {
        $this->state_factory        = $state_factory;
        $this->transition_extractor = $transition_extractor;
    }

    /**
     * @throws NoTransitionForStateException
     */
    public function getReferenceTransitionForCurrentState(Tracker_Artifact $artifact): Transition
    {
        $workflow = $artifact->getWorkflow();

        if ($workflow === null || ! $workflow->isUsed() || $workflow->isAdvanced()) {
            throw new NoTransitionForStateException();
        }

        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset === null) {
            throw new NoTransitionForStateException();
        }

        $field = $workflow->getField();

        $current_value   = $last_changeset->getValue($field);
        if ($current_value === null) {
            throw new NoTransitionForStateException();
        }

        $current_status = (int) current($current_value->getValue());

        $state = $this->state_factory->getStateFromValueId($workflow, $current_status);

        return $this->transition_extractor->extractReferenceTransitionFromState($state);
    }
}
