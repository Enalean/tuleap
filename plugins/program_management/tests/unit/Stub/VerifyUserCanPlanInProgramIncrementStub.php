<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanPlanInProgramIncrement;

final class VerifyUserCanPlanInProgramIncrementStub implements VerifyUserCanPlanInProgramIncrement
{
    private bool $can_plan;

    private function __construct(bool $can_plan)
    {
        $this->can_plan = $can_plan;
    }

    public static function buildCanNotPlan(): self
    {
        return new self(false);
    }

    public static function buildCanPlan(): self
    {
        return new self(true);
    }

    public function userCanPlan(
        ProgramIncrementIdentifier $program_increment_identifier,
        UserIdentifier $user_identifier
    ): bool {
        return $this->can_plan;
    }

    public function userCanPlanAndPrioritize(
        ProgramIncrementIdentifier $program_increment_identifier,
        UserCanPrioritize $user_identifier
    ): bool {
        return $this->can_plan;
    }
}
