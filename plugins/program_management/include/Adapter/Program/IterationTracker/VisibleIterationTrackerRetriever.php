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

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class VisibleIterationTrackerRetriever implements RetrieveVisibleIterationTracker
{
    public function __construct(
        private RetrieveIterationTracker $iteration_tracker_retriever,
        private \TrackerFactory $tracker_factory,
        private RetrieveUser $retrieve_user,
    ) {
    }

    public function retrieveVisibleIterationTracker(
        ProgramIdentifier $program,
        UserIdentifier $user_identifier,
    ): ?TrackerReference {
        $iteration_tracker_id = $this->iteration_tracker_retriever->getIterationTrackerId(
            $program
        );

        if (! $iteration_tracker_id) {
            return null;
        }

        $iteration_tracker = $this->getValidTracker(
            $iteration_tracker_id
        );

        $user = $this->retrieve_user->getUserWithId($user_identifier);
        if (! $iteration_tracker->userCanView($user)) {
            return null;
        }

        return TrackerReferenceProxy::fromTracker($iteration_tracker);
    }

    /**
     * @throws ProgramTrackerNotFoundException
     */
    private function getValidTracker(int $tracker_id): \Tuleap\Tracker\Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new ProgramTrackerNotFoundException($tracker_id);
        }

        return $tracker;
    }
}
