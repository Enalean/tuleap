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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;

final class RetrieveProgramIncrementTrackerStub implements RetrieveProgramIncrementTracker
{
    private ?int $tracker_id;

    private function __construct(?int $tracker_id)
    {
        $this->tracker_id = $tracker_id;
    }

    #[\Override]
    public function getProgramIncrementTrackerId(int $project_id): ?int
    {
        return $this->tracker_id;
    }

    #[\Override]
    public function getProgramIncrementTrackerIdFromProgramIncrement(ProgramIncrementIdentifier $program_increment): int
    {
        if ($this->tracker_id === null) {
            throw new \LogicException(
                'Expected stub to return a valid program increment tracker id, but it was setup with null'
            );
        }
        return $this->tracker_id;
    }

    public static function withValidTracker(int $tracker_id): self
    {
        return new self($tracker_id);
    }

    public static function withNoProgramIncrementTracker(): self
    {
        return new self(null);
    }
}
