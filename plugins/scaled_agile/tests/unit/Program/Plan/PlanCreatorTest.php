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

namespace Tuleap\ScaledAgile\Program\Plan;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesAPlan(): void
    {
        $program_adapter = \Mockery::mock(BuildProgram::class);
        $tracker_adapter = \Mockery::mock(BuildTracker::class);

        $project_id = 101;
        $program_increment_tracker_id = 1;
        $plannable_tracker_id         = 2;

        $user = UserTestBuilder::aUser()->build();

        $program_adapter->shouldReceive('buildProgramProject')
            ->with($project_id, $user)->once()
            ->andReturn(new Program($project_id));
        $tracker_adapter->shouldReceive('buildProgramIncrementTracker')
            ->with($program_increment_tracker_id, $project_id)->once()
            ->andReturn(new ProgramIncrementTracker($program_increment_tracker_id));
        $tracker_adapter->shouldReceive('buildPlannableTrackerList')
            ->with([$plannable_tracker_id], $project_id)->once()
            ->andReturn([$plannable_tracker_id => new ProgramIncrementTracker($plannable_tracker_id)]);

        $plan_dao = \Mockery::mock(PlanStore::class);
        $plan_dao->shouldReceive('save')->with(\Mockery::type(Plan::class))->once();

        $plan_adapter = new PlanCreator($program_adapter, $tracker_adapter, $plan_dao);
        $plan_adapter->create($user, $project_id, $program_increment_tracker_id, [$plannable_tracker_id]);
    }

    public function testItThrowsAnExceptionWhenProgramIncrementTrackerIsInPlannableTracker(): void
    {
        $program_adapter = \Mockery::mock(BuildProgram::class);
        $tracker_adapter = \Mockery::mock(BuildTracker::class);
        $plan_dao        = \Mockery::mock(PlanStore::class);

        $user = UserTestBuilder::aUser()->build();

        $project_id = 101;
        $program_increment_tracker_id = 1;
        $plannable_tracker_id         = 1;

        $this->expectException(CannotPlanIntoItselfException::class);

        $plan_adapter = new PlanCreator($program_adapter, $tracker_adapter, $plan_dao);
        $plan_adapter->create($user, $project_id, $program_increment_tracker_id, [$plannable_tracker_id]);
    }
}
