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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveProgramIncrementsStub implements RetrieveProgramIncrements
{
    /**
     * @param ProgramIncrement[] $open_program_increments
     */
    private function __construct(private array $open_program_increments)
    {
    }

    public static function withDefaults(): self
    {
        return new self([]);
    }

    /**
     * @param ProgramIncrement[] $program_increments
     */
    public static function withOpenProgramIncrements(array $program_increments): self
    {
        return new self($program_increments);
    }

    public function retrieveOpenProgramIncrements(ProgramIdentifier $program, UserIdentifier $user_identifier): array
    {
        return $this->open_program_increments;
    }

    public function retrieveProgramIncrementById(UserIdentifier $user_identifier, ProgramIncrementIdentifier $increment_identifier): ?ProgramIncrement
    {
        foreach ($this->open_program_increments as $program_increment) {
            if ($program_increment->id === $increment_identifier->getId()) {
                return $program_increment;
            }
        }

        return null;
    }
}
