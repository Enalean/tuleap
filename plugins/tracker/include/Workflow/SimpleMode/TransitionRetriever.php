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

namespace Tuleap\Tracker\Workflow\SimpleMode;

use Tracker_Artifact;
use Transition;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class TransitionRetriever
{
    /**
     * @var \Workflow_TransitionDao
     */
    private $transition_dao;
    /**
     * @var \TransitionFactory
     */
    private $transition_factory;

    public function __construct(\Workflow_TransitionDao $transition_dao, \TransitionFactory $transition_factory)
    {
        $this->transition_dao     = $transition_dao;
        $this->transition_factory = $transition_factory;
    }

    /**
     * @throws NoTransitionForStateException
     */
    public function getFirstTransitionForCurrentState(Tracker_Artifact $artifact) : Transition
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

        return $this->getFirstTransitionForDestinationState($workflow, $current_status);
    }

    /**
     * @throws NoTransitionForStateException
     */
    private function getFirstTransitionForDestinationState(\Workflow $workflow, int $to): Transition
    {
        $row_first_non_new = $this->transition_dao->searchFirstTransitionNotFromNew(
            (int) $workflow->getId(),
            $to
        );

        if ($row_first_non_new !== false) {
            return $this->transition_factory->getInstanceFromRow($row_first_non_new);
        }

        $row_only_new = $this->transition_dao->searchOnlyTransitionFromNew(
            (int) $workflow->getId(),
            $to
        );

        if ($row_only_new !== false) {
            return $this->transition_factory->getInstanceFromRow($row_only_new);
        }

        throw new NoTransitionForStateException();
    }
}
