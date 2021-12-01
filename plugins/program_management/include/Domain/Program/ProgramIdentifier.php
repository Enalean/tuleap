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

namespace Tuleap\ProgramManagement\Domain\Program;

use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * A program is a Tuleap Project that hosts Program Increments and Iterations and synchronizes them with Teams.
 * This represents its project ID number.
 * I have at least one Team. For a Program not yet configured, see ProgramForAdministrationIdentifier
 * @see ProgramForAdministrationIdentifier
 * @psalm-immutable
 */
final class ProgramIdentifier
{
    private function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public static function fromId(
        BuildProgram $build_program,
        int $id,
        UserIdentifier $user,
        ?PermissionBypass $bypass,
    ): self {
        $build_program->ensureProgramIsAProject($id, $user, $bypass);

        return new self($id);
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public static function fromProgramIncrement(
        RetrieveProgramOfProgramIncrement $program_retriever,
        BuildProgram $program_builder,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): self {
        $program_id = $program_retriever->getProgramOfProgramIncrement($program_increment);
        $program_builder->ensureProgramIsAProject($program_id, $user, null);

        return new self($program_id);
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public static function fromIteration(
        RetrieveProgramOfIteration $program_retriever,
        BuildProgram $program_builder,
        IterationIdentifier $iteration,
        UserIdentifier $user,
    ): self {
        $program_id = $program_retriever->getProgramOfIteration($iteration);
        $program_builder->ensureProgramIsAProject($program_id, $user, null);

        return new self($program_id);
    }
}
