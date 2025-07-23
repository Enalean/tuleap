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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;

final class RetrieveProgramOfProgramIncrementStub implements RetrieveProgramOfProgramIncrement
{
    private function __construct(private ?int $program_id)
    {
    }

    public static function withProgram(int $program_id): self
    {
        return new self($program_id);
    }

    public static function withNoProgram(): self
    {
        return new self(null);
    }

    #[\Override]
    public function getProgramOfProgramIncrement(ProgramIncrementIdentifier $program_increment): int
    {
        if ($this->program_id === null) {
            throw new ProgramIncrementHasNoProgramException($program_increment);
        }
        return $this->program_id;
    }
}
