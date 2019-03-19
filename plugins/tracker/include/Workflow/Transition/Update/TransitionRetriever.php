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

namespace Tuleap\Tracker\Workflow\Transition\Update;

use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
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
     * @throws NoSiblingTransitionException
     */
    public function getSiblingTransitions(\Transition $transition): TransitionCollection
    {
        $rows = $this->transition_dao->searchSiblings(
            $transition->workflow_id,
            $transition->getIdTo(),
            $transition->getId()
        );
        if ($rows === false) {
            throw new NoSiblingTransitionException();
        }

        $siblings = [];
        foreach ($rows as $row) {
            $siblings[] = $this->transition_factory->getInstanceFromRow($row);
        }
        return new TransitionCollection(...$siblings);
    }

    /**
     * @throws NoSiblingTransitionException
     */
    public function getFirstSiblingTransition(\Transition $transition): \Transition
    {
        $rows = $this->transition_dao->searchSiblings(
            $transition->workflow_id,
            $transition->getIdTo(),
            $transition->getId()
        );
        if ($rows === false) {
            throw new NoSiblingTransitionException();
        }

        $row = $this->searchSiblingTransitionInRows($rows);
        return $this->transition_factory->getInstanceFromRow($row);
    }

    /**
     * @throws NoTransitionForStateException
     */
    public function getFirstTransitionForDestinationState(\Workflow $workflow, int $to): \Transition
    {
        $row = $this->transition_dao->searchFirstTransition(
            (int) $workflow->getId(),
            $to
        );
        if ($row === false) {
            throw new NoTransitionForStateException();
        }

        return $this->transition_factory->getInstanceFromRow($row);
    }

    /**
     * @throws NoSiblingTransitionException
     */
    private function searchSiblingTransitionInRows($siblings): array
    {
        $transition_from_new_artifact = null;
        foreach ($siblings as $row) {
            if ($row['from_id'] === '0') {
                $transition_from_new_artifact = $row;
            } else {
                return $row;
            }
        }
        if ($transition_from_new_artifact !== null) {
            return $transition_from_new_artifact;
        }
        throw new NoSiblingTransitionException();
    }
}
