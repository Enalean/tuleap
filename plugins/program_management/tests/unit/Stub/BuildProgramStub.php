<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Test\Builders\UserTestBuilder;

final class BuildProgramStub implements BuildProgram
{
    private bool $is_a_program;
    private bool $is_valid_to_be_created_program;
    private bool $is_access_allowed;

    private function __construct(
        bool $is_a_program,
        bool $is_valid_to_be_created_program,
        bool $is_access_allowed
    ) {
        $this->is_a_program                   = $is_a_program;
        $this->is_valid_to_be_created_program = $is_valid_to_be_created_program;
        $this->is_access_allowed              = $is_access_allowed;
    }

    public static function stubValidProgram(): self
    {
        return new self(true, false, true);
    }

    public static function stubInvalidProgram(): self
    {
        return new self(false, false, true);
    }

    public static function stubInvalidProgramAccess(): self
    {
        return new self(true, false, false);
    }

    public static function stubValidToBeCreatedProgram(): self
    {
        return new self(true, true, true);
    }

    public function ensureProgramIsAProject(int $project_id, UserIdentifier $user_identifier): void
    {
        if (! $this->is_a_program) {
            throw new ProjectIsNotAProgramException($project_id);
        }

        if (! $this->is_access_allowed) {
            $user = UserTestBuilder::aUser()->build();
            throw new ProgramAccessException($project_id, $user);
        }
    }

    public function ensureProgramIsProjectAndUserIsAdminOf(int $id, UserIdentifier $user): void
    {
        if ($this->is_valid_to_be_created_program) {
            return;
        }

        throw new \LogicException("Not implemented");
    }
}
