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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class RetrieveIterationTrackerStub implements RetrieveIterationTracker
{
    private function __construct(private ?int $tracker_id)
    {
    }

    #[\Override]
    public function getIterationTrackerId(ProgramIdentifier $program_identifier): ?int
    {
        return $this->tracker_id;
    }

    #[\Override]
    public function getIterationTrackerIdFromIteration(IterationIdentifier $iteration): int
    {
        if ($this->tracker_id === null) {
            throw new \LogicException(
                'Expected stub to return a valid iteration tracker id, but it was setup with null'
            );
        }
        return $this->tracker_id;
    }

    public static function withValidTracker(int $tracker_id): self
    {
        return new self($tracker_id);
    }

    public static function withNoIterationTracker(): self
    {
        return new self(null);
    }
}
