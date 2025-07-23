<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveProgramIncrementStub implements RetrieveProgramIncrement
{
    private function __construct(private bool $should_return_null, private array $program_increments)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveProgramIncrements(
        ProgramIncrement $first_pi,
        ProgramIncrement ...$other_pis,
    ): self {
        return new self(false, [$first_pi, ...$other_pis]);
    }

    public static function withNoVisibleProgramIncrement(): self
    {
        return new self(true, []);
    }

    #[\Override]
    public function retrieveProgramIncrementById(
        UserIdentifier $user_identifier,
        ProgramIncrementIdentifier $increment_identifier,
    ): ?ProgramIncrement {
        if ($this->should_return_null) {
            return null;
        }
        if (count($this->program_increments) > 0) {
            return array_shift($this->program_increments);
        }
        throw new \LogicException('No program increment configured');
    }
}
