<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Source;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I contain the Timebox tracker and all its Mirrored Timebox trackers from
 * the Program's Teams.
 * @psalm-immutable
 */
final class SourceTrackerCollection
{
    /**
     * @param TrackerReference[] $source_trackers
     */
    private function __construct(private array $source_trackers)
    {
    }

    /**
     * @throws ProgramTrackerNotFoundException
     * @throws ProgramHasNoProgramIncrementTrackerException
     */
    public static function fromProgramAndTeamTrackers(
        RetrieveVisibleProgramIncrementTracker $retriever,
        ProgramIdentifier $program,
        TrackerCollection $team_trackers,
        UserIdentifier $user_identifier,
    ): self {
        $trackers = [$retriever->retrieveVisibleProgramIncrementTracker($program, $user_identifier)];
        foreach ($team_trackers->getTrackers() as $team_tracker) {
            $trackers[] = $team_tracker;
        }
        return new self($trackers);
    }

    /**
     * @throws ProgramTrackerNotFoundException
     */
    public static function fromIterationAndTeamTrackers(
        RetrieveVisibleIterationTracker $retriever,
        ProgramIdentifier $program,
        TrackerCollection $team_trackers,
        UserIdentifier $user_identifier,
    ): ?self {
        $iteration_tracker = $retriever->retrieveVisibleIterationTracker($program, $user_identifier);

        if ($iteration_tracker === null) {
            return null;
        }

        $trackers = [$iteration_tracker];
        foreach ($team_trackers->getTrackers() as $team_tracker) {
            $trackers[] = $team_tracker;
        }
        return new self($trackers);
    }

    /**
     * @return TrackerReference[]
     */
    public function getSourceTrackers(): array
    {
        return $this->source_trackers;
    }

    /**
     * @return list<int>
     */
    public function getSourceTrackerIds(): array
    {
        return array_values(array_map(
            static fn(TrackerReference $tracker) => $tracker->getId(),
            $this->source_trackers
        ));
    }
}
