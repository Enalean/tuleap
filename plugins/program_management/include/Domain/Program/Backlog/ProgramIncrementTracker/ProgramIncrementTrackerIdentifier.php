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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

/**
 * I am the ID (identifier) of the Program Increment Tracker
 * @psalm-immutable
 */
final class ProgramIncrementTrackerIdentifier implements TrackerIdentifier
{
    private function __construct(private int $id)
    {
    }

    public static function fromId(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        TrackerIdentifier $tracker,
    ): ?self {
        if (! $program_increment_verifier->isProgramIncrementTracker($tracker->getId())) {
            return null;
        }
        return new self($tracker->getId());
    }

    public static function fromProgramIncrement(
        RetrieveProgramIncrementTracker $tracker_retriever,
        ProgramIncrementIdentifier $program_increment,
    ): self {
        return new self($tracker_retriever->getProgramIncrementTrackerIdFromProgramIncrement($program_increment));
    }

    public static function fromPlanConfiguration(
        int $program_increment_tracker_id,
    ): self {
        return new self($program_increment_tracker_id);
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
