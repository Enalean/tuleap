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
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;

final class BuildProgramStub implements BuildProgram
{
    private bool $is_allowed;
    private bool $is_valid_program_for_management;
    private bool $is_valid_to_be_created_program;

    private function __construct(
        bool $is_allowed,
        bool $is_valid_program_for_management,
        bool $is_valid_to_be_created_program
    ) {
        $this->is_allowed                      = $is_allowed;
        $this->is_valid_program_for_management = $is_valid_program_for_management;
        $this->is_valid_to_be_created_program  = $is_valid_to_be_created_program;
    }

    public static function stubValidProgram(): self
    {
        return new self(true, false, false);
    }

    public static function stubInvalidProgram(): self
    {
        return new self(false, false, false);
    }

    public static function stubValidProgramForManagement(): self
    {
        return new self(true, true, false);
    }

    public static function stubValidToBeCreatedProgram(): self
    {
        return new self(true, false, true);
    }

    public function ensureProgramIsAProject(int $project_id, \PFUser $user): void
    {
        if (! $this->is_allowed) {
            throw new ProjectIsNotAProgramException($project_id);
        }
    }

    public function ensureProgramIsAProjectForManagement(int $id, \PFUser $user): void
    {
        if ($this->is_valid_program_for_management) {
            return;
        }
        throw new \LogicException("Not implemented");
    }

    public function ensureProgramIsProjectAndUserIsAdminOf(int $id, \PFUser $user): void
    {
        if ($this->is_valid_to_be_created_program) {
            return;
        }

        throw new \LogicException("Not implemented");
    }
}
