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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class ProgramIncrementBuilder
{
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var RetrieveProgramIncrements
     */
    private $program_increments_retriever;

    public function __construct(BuildProgram $build_program, RetrieveProgramIncrements $program_increments_retriever)
    {
        $this->build_program                = $build_program;
        $this->program_increments_retriever = $program_increments_retriever;
    }

    /**
     * @return ProgramIncrement[]
     *
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     */
    public function buildOpenProgramIncrements(int $potential_program_id, \PFUser $user): array
    {
        $program = ProgramIdentifier::fromId($this->build_program, $potential_program_id, $user);
        return $this->program_increments_retriever->retrieveOpenProgramIncrements($program, $user);
    }
}
