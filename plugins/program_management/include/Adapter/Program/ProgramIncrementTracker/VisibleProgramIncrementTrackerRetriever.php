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

namespace Tuleap\ProgramManagement\Adapter\Program\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class VisibleProgramIncrementTrackerRetriever implements RetrieveVisibleProgramIncrementTracker
{
    public function __construct(
        private RetrieveProgramIncrementTracker $program_increment_tracker_retriever,
        private \TrackerFactory $tracker_factory,
        private RetrieveUser $retrieve_user,
    ) {
    }

    #[\Override]
    public function retrieveVisibleProgramIncrementTracker(ProgramIdentifier $program, UserIdentifier $user_identifier): TrackerReference
    {
        $program_id                   = $program->getId();
        $program_increment_tracker_id = $this->program_increment_tracker_retriever->getProgramIncrementTrackerId(
            $program_id
        );
        if (! $program_increment_tracker_id) {
            throw new ProgramHasNoProgramIncrementTrackerException($program_id);
        }
        $program_increment_tracker = $this->getValidTracker(
            $program_increment_tracker_id
        );

        $user = $this->retrieve_user->getUserWithId($user_identifier);
        if (! $program_increment_tracker->userCanView($user)) {
            throw new ProgramTrackerNotFoundException($program_increment_tracker_id);
        }

        return TrackerReferenceProxy::fromTracker($program_increment_tracker);
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
