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
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCreatesAPlan(): void
    {
        $program_adapter = BuildProgramStub::stubValidProgramForManagement();
        $tracker_adapter = $this->createMock(BuildTracker::class);
        $build_ugroups   = $this->createMock(BuildProgramUserGroup::class);

        $project_id           = 102;
        $plannable_tracker_id = 2;

        $user = UserTestBuilder::aUser()->build();

        $program = ProgramForManagement::fromId($program_adapter, $project_id, $user);
        $tracker_adapter->expects(self::exactly(2))->method('checkTrackerIsValid');
        $tracker_adapter->expects(self::once())->method('buildPlannableTrackerList')->with(
            [$plannable_tracker_id],
            $project_id
        )->willReturn(
            [$plannable_tracker_id => ProgramPlannableTracker::build(
                $tracker_adapter,
                $plannable_tracker_id,
                $project_id
            )]
        );
        $build_ugroups->method('buildProgramUserGroups')->willReturn([$program]);

        $plan_dao = $this->createMock(PlanStore::class);
        $plan_dao->expects(self::once())->method('save')->with(self::isInstanceOf(Plan::class));
        $plan_program_increment_change = new PlanProgramIncrementChange(1, 'Program Increments', 'program increment');
        $plan_change                   = PlanChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $project_id,
            [$plannable_tracker_id],
            ['102_4']
        );

        $plan_adapter = new PlanCreator($program_adapter, $tracker_adapter, $build_ugroups, $plan_dao);
        $plan_adapter->create($plan_change);
    }
}
