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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsAProgramOrUsedInPlanChecker;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProjectIsAProgramOrUsedInPlanCheckerStub implements ProjectIsAProgramOrUsedInPlanChecker
{
    private bool $is_a_program;
    private bool $is_access_allowed;

    private function __construct(
        bool $is_a_program,
        bool $is_access_allowed,
    ) {
        $this->is_a_program      = $is_a_program;
        $this->is_access_allowed = $is_access_allowed;
    }

    public static function stubValidProgram(): self
    {
        return new self(true, true);
    }

    #[\Override]
    public function ensureProjectIsAProgramOrIsPartOfPlan(int $project_id, UserIdentifier $user): void
    {
        if (! $this->is_a_program) {
            throw new ProjectIsNotAProgramException($project_id);
        }

        if (! $this->is_access_allowed) {
            throw new ProgramAccessException(
                $project_id,
                UserReferenceStub::withIdAndName($user->getId(), 'Daniel Bialaszewski')
            );
        }
    }
}
