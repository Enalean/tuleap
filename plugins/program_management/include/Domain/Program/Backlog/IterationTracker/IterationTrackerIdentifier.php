<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I am the id (identifier) of the Iteration Tracker
 * @psalm-immutable
 */
final class IterationTrackerIdentifier implements TrackerIdentifier
{
    private function __construct(private int $id)
    {
    }

    public static function fromTrackerIdentifier(
        VerifyIsIterationTracker $verify_is_iteration_tracker,
        TrackerIdentifier $tracker,
    ): ?self {
        if (! $verify_is_iteration_tracker->isIterationTracker($tracker->getId())) {
            return null;
        }
        return new self($tracker->getId());
    }

    public static function fromIteration(
        RetrieveIterationTracker $tracker_retriever,
        IterationIdentifier $iteration,
    ): self {
        return new self($tracker_retriever->getIterationTrackerIdFromIteration($iteration));
    }

    public static function fromProgram(
        RetrieveVisibleIterationTracker $tracker_retriever,
        ProgramIdentifier $program,
        UserIdentifier $user,
    ): ?self {
        try {
            $tracker = $tracker_retriever->retrieveVisibleIterationTracker($program, $user);
        } catch (ProgramTrackerNotFoundException) {
            return null;
        }
        if ($tracker === null) {
            return null;
        }
        return new self($tracker->getId());
    }

    public static function fromProgramIncrement(
        RetrieveProgramOfProgramIncrement $program_retriever,
        BuildProgram $program_builder,
        RetrieveIterationTracker $iteration_tracker_retriever,
        ProgramIncrementIdentifier $program_increment_identifier,
        UserIdentifier $user_identifier,
    ): ?self {
        $tracker_identifier = $iteration_tracker_retriever->getIterationTrackerId(
            ProgramIdentifier::fromProgramIncrement(
                $program_retriever,
                $program_builder,
                $program_increment_identifier,
                $user_identifier
            )
        );

        if (! $tracker_identifier) {
            return null;
        }

        return new self(
            $tracker_identifier
        );
    }

    public static function fromPlanConfiguration(int $iteration_tracker_id): self
    {
        return new self($iteration_tracker_id);
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
