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
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class TransitionExtractor
{
    /**
     * @throws NoTransitionForStateException
     */
    public function extractReferenceTransitionFromState(State $state): Transition
    {
        $transitions = $state->getTransitions();

        if (count($transitions) === 1) {
            return $transitions[0];
        }

        foreach ($transitions as $transition) {
            \assert($transition instanceof Transition);
            if ($transition->getIdFrom() !== '') {
                return $transition;
            }
        }

        throw new NoTransitionForStateException();
    }

    public function extractSiblingTransitionsFromState(State $state, Transition $transition): array
    {
        $sibling_transitions = [];
        foreach ($state->getTransitions() as $state_transition) {
            if (
                $transition->getIdFrom() === $state_transition->getIdFrom() &&
                $transition->getIdTo() === $state_transition->getIdTo()
            ) {
                continue;
            }

            $sibling_transitions[] = $state_transition;
        }

        return $sibling_transitions;
    }
}
