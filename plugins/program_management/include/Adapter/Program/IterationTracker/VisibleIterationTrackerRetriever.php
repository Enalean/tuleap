<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\IterationTracker;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;

final class VisibleIterationTrackerRetriever implements RetrieveVisibleIterationTracker
{
    private RetrieveIterationTracker $iteration_tracker_retriever;
    private \TrackerFactory $tracker_factory;

    public function __construct(
        RetrieveIterationTracker $iteration_tracker_retriever,
        \TrackerFactory $tracker_factory
    ) {
        $this->iteration_tracker_retriever = $iteration_tracker_retriever;
        $this->tracker_factory             = $tracker_factory;
    }

    public function retrieveVisibleIterationTracker(ProgramIdentifier $program, \PFUser $user): ?\Tracker
    {
        $program_id           = $program->getId();
        $iteration_tracker_id = $this->iteration_tracker_retriever->getIterationTrackerId(
            $program_id
        );

        if (! $iteration_tracker_id) {
            return null;
        }

        $iteration_tracker = $this->getValidTracker(
            $iteration_tracker_id
        );

        if (! $iteration_tracker->userCanView($user)) {
            return null;
        }

        return $iteration_tracker;
    }

    /**
     * @throws ProgramTrackerNotFoundException
     */
    private function getValidTracker(int $tracker_id): \Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new ProgramTrackerNotFoundException($tracker_id);
        }

        return $tracker;
    }
}
