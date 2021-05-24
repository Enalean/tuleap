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

use Tuleap\ProgramManagement\Domain\Program\ProgramForManagement;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCreatesAPlan(): void
    {
        $program_adapter = BuildProgramStub::stubValidProgramForManagement();
        $tracker_adapter = BuildTrackerStub::buildTrackerIsValidAndGetPlannableTrackerList();
        $build_ugroups   = $this->createMock(BuildProgramUserGroup::class);

        $project_id           = 102;
        $plannable_tracker_id = 2;

        $user = UserTestBuilder::aUser()->build();

        $program = ProgramForManagement::fromId($program_adapter, $project_id, $user);
        $build_ugroups->method('buildProgramUserGroups')->willReturn([$program]);

        $plan_dao = $this->createMock(PlanStore::class);
        $plan_dao->expects(self::once())->method('save')->with(self::isInstanceOf(Plan::class));
        $plan_program_increment_change = new PlanProgramIncrementChange(1, 'Program Increments', 'program increment');
        $iteration_representation      = new PlanIterationChange(150, null, null);
        $plan_change                   = PlanChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $project_id,
            [$plannable_tracker_id],
            ['102_4'],
            $iteration_representation
        );

        $plan_adapter = new PlanCreator($program_adapter, $tracker_adapter, $build_ugroups, $plan_dao);
        $plan_adapter->create($plan_change);
    }
}
