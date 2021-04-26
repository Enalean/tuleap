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

namespace Tuleap\ProgramManagement\Program\Plan;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\ProgramForManagement;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesAPlan(): void
    {
        $program_adapter = \Mockery::mock(BuildProgram::class);
        $tracker_adapter = \Mockery::mock(BuildTracker::class);
        $build_ugroups   = \Mockery::mock(BuildProgramUserGroup::class);

        $project_id                   = 101;
        $program_increment_tracker_id = 1;
        $plannable_tracker_id         = 2;

        $user = UserTestBuilder::aUser()->build();

        $program = new ProgramForManagement($project_id);
        $program_adapter->shouldReceive('buildExistingProgramProjectForManagement')
            ->with($project_id, $user)->once()
            ->andReturn($program);
        $tracker_adapter->shouldReceive('getValidTracker')
            ->with($program_increment_tracker_id, $project_id)->once()
            ->andReturn(TrackerTestBuilder::aTracker()->withId($program_increment_tracker_id)->build());
        $tracker_adapter->shouldReceive('buildPlannableTrackerList')
            ->with([$plannable_tracker_id], $project_id)->once()
            ->andReturn([$plannable_tracker_id => new ProgramPlannableTracker($plannable_tracker_id)]);
        $build_ugroups->shouldReceive('buildProgramUserGroups')->andReturn([$program]);

        $plan_dao = \Mockery::mock(PlanStore::class);
        $plan_dao->shouldReceive('save')->with(\Mockery::type(Plan::class))->once();

        $plan_adapter = new PlanCreator($program_adapter, $tracker_adapter, $build_ugroups, $plan_dao);
        $plan_adapter->create(
            $user,
            $project_id,
            $program_increment_tracker_id,
            [$plannable_tracker_id],
            ['102_4'],
            "Program Increments",
            "program increment"
        );
    }

    public function testItThrowsAnExceptionWhenProgramIncrementTrackerIsInPlannableTracker(): void
    {
        $program_adapter = \Mockery::mock(BuildProgram::class);
        $tracker_adapter = \Mockery::mock(BuildTracker::class);
        $build_ugroups   = \Mockery::mock(BuildProgramUserGroup::class);
        $plan_dao        = \Mockery::mock(PlanStore::class);

        $user = UserTestBuilder::aUser()->build();

        $project_id                   = 101;
        $program_increment_tracker_id = 1;
        $plannable_tracker_id         = 1;

        $this->expectException(CannotPlanIntoItselfException::class);

        $plan_adapter = new PlanCreator($program_adapter, $tracker_adapter, $build_ugroups, $plan_dao);
        $plan_adapter->create(
            $user,
            $project_id,
            $program_increment_tracker_id,
            [$plannable_tracker_id],
            ['101_4'],
            "Program Increments",
            "program increment"
        );
    }
}
