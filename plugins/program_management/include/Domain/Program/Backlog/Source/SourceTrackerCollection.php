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
use Tuleap\ProgramManagement\Domain\ProgramTracker;

/**
 * I contain the Timebox tracker and all its Mirrored Timebox trackers from
 * the Program's Teams.
 * @psalm-immutable
 */
final class SourceTrackerCollection
{
    /**
     * @var ProgramTracker[]
     */
    private array $source_trackers;

    /**
     * @param ProgramTracker[] $source_trackers
     */
    private function __construct(array $source_trackers)
    {
        $this->source_trackers = $source_trackers;
    }

    /**
     * @throws ProgramTrackerNotFoundException
     * @throws ProgramHasNoProgramIncrementTrackerException
     */
    public static function fromProgramAndTeamTrackers(
        RetrieveVisibleProgramIncrementTracker $retriever,
        ProgramIdentifier $program,
        TrackerCollection $team_trackers,
        \PFUser $user
    ): self {
        $trackers = [ProgramTracker::buildProgramIncrementTrackerFromProgram($retriever, $program, $user)];
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
        \PFUser $user
    ): ?self {
        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $user);

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
     * @return ProgramTracker[]
     */
    public function getSourceTrackers(): array
    {
        return $this->source_trackers;
    }

    /**
     * @return int[]
     * @psalm-mutation-free
     */
    public function getSourceTrackerIds(): array
    {
        return array_map(
            static fn(ProgramTracker $tracker) => $tracker->getTrackerId(),
            $this->source_trackers
        );
    }
}
