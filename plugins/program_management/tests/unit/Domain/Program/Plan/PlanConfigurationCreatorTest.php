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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Tests\Stub\CheckNewIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewPlannableTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramUserGroupStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SaveNewPlanConfigurationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanConfigurationCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID                   = 102;
    private const ADMINISTRATORS_USER_GROUP_ID = 4;
    private SaveNewPlanConfigurationStub $plan_saver;

    protected function setUp(): void
    {
        $this->plan_saver = SaveNewPlanConfigurationStub::withCount();
    }

    private function createPlan(): void
    {
        $program_increment_change = new PlanProgramIncrementChange(
            30,
            'Program Increments',
            'program increment'
        );

        $iteration_change = new PlanIterationChange(
            55,
            'Iterations',
            'iteration'
        );

        $plan_change = PlanConfigurationChange::fromProgramIncrementAndRaw(
            $program_increment_change,
            UserReferenceStub::withDefaults(),
            self::PROGRAM_ID,
            [7, 44],
            [self::PROGRAM_ID . '_' . self::ADMINISTRATORS_USER_GROUP_ID],
            $iteration_change
        );
        $creator     = new PlanConfigurationCreator(
            CheckNewProgramIncrementTrackerStub::withValidTracker(),
            CheckNewPlannableTrackerStub::withValidTracker(),
            CheckNewIterationTrackerStub::withValidTracker(),
            RetrieveProgramUserGroupStub::withValidUserGroups(self::ADMINISTRATORS_USER_GROUP_ID),
            $this->plan_saver,
            RetrieveProjectStub::withValidProjects(ProjectIdentifierStub::buildWithId(self::PROGRAM_ID)),
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator()
        );

        $creator->create($plan_change);
    }

    public function testItCreatesAndSavedAPlan(): void
    {
        $this->createPlan();
        self::assertSame(1, $this->plan_saver->getCallCount());
    }
}
