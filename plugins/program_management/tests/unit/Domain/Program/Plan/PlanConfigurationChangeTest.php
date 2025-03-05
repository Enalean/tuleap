<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanConfigurationChangeTest extends TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 16;

    private UserReference $user_identifier;

    protected function setUp(): void
    {
        $this->user_identifier = UserReferenceStub::withDefaults();
    }

    public function testItThrowsWhenProgramIncrementTrackerIdCanAlsoBePlanned(): void
    {
        $tracker_ids_that_can_be_planned = [99, self::PROGRAM_INCREMENT_TRACKER_ID, 67];
        $plan_program_increment_change   = new PlanProgramIncrementChange(self::PROGRAM_INCREMENT_TRACKER_ID, null, null);

        $this->expectException(ProgramIncrementCannotPlanIntoItselfException::class);
        PlanConfigurationChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $this->user_identifier,
            101,
            $tracker_ids_that_can_be_planned,
            ['1_101'],
            null
        );
    }

    public function testItThrowsWhenIterationTrackerIdCanAlsoBePlanned(): void
    {
        $tracker_ids_that_can_be_planned = [99, 67];
        $plan_program_increment_change   = new PlanProgramIncrementChange(self::PROGRAM_INCREMENT_TRACKER_ID, null, null);
        $iteration_representation        = new PlanIterationChange(99, null, null);

        $this->expectException(IterationCannotBePlannedException::class);
        PlanConfigurationChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $this->user_identifier,
            101,
            $tracker_ids_that_can_be_planned,
            ['1_101'],
            $iteration_representation
        );
    }

    public function testItThrowsWhenProgramIncrementAndIterationAreTheSameTracker(): void
    {
        $plan_program_increment_change   = new PlanProgramIncrementChange(16, 'Releases', 'release');
        $user                            = $this->user_identifier;
        $project_id                      = 101;
        $tracker_ids_that_can_be_planned = [99, 67];
        $can_possibly_prioritize_ugroups = ['198', '101_3'];
        $plan_iteration_change           = new PlanIterationChange(16, 'Iterations', 'iteration');

        $this->expectException(ProgramIncrementAndIterationCanNotBeTheSameTrackerException::class);
        PlanConfigurationChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $project_id,
            $tracker_ids_that_can_be_planned,
            $can_possibly_prioritize_ugroups,
            $plan_iteration_change
        );
    }

    public function testItBuildsAValidPlanConfigurationChange(): void
    {
        $plan_program_increment_change   = new PlanProgramIncrementChange(16, 'Releases', 'release');
        $user                            = $this->user_identifier;
        $project_id                      = 101;
        $tracker_ids_that_can_be_planned = [99, 67];
        $can_possibly_prioritize_ugroups = ['198', '101_3'];

        $plan_change = PlanConfigurationChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $project_id,
            $tracker_ids_that_can_be_planned,
            $can_possibly_prioritize_ugroups,
            null
        );

        self::assertSame($plan_program_increment_change, $plan_change->program_increment_change);
        self::assertSame($user, $plan_change->user);
        self::assertSame($project_id, $plan_change->project_id);
        self::assertSame($tracker_ids_that_can_be_planned, $plan_change->tracker_ids_that_can_be_planned);
        self::assertSame($can_possibly_prioritize_ugroups, $plan_change->can_possibly_prioritize_ugroups);
        self::assertNull($plan_change->iteration);
    }

    public function testItBuildsAValidPlanConfigurationChangeWithIterationChange(): void
    {
        $plan_program_increment_change   = new PlanProgramIncrementChange(16, 'Releases', 'release');
        $user                            = $this->user_identifier;
        $project_id                      = 101;
        $tracker_ids_that_can_be_planned = [99, 67];
        $can_possibly_prioritize_ugroups = ['198', '101_3'];
        $plan_iteration_change           = new PlanIterationChange(130, 'Iterations', 'iteration');

        $plan_change = PlanConfigurationChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $project_id,
            $tracker_ids_that_can_be_planned,
            $can_possibly_prioritize_ugroups,
            $plan_iteration_change
        );

        self::assertSame($plan_program_increment_change, $plan_change->program_increment_change);
        self::assertSame($user, $plan_change->user);
        self::assertSame($project_id, $plan_change->project_id);
        self::assertSame($tracker_ids_that_can_be_planned, $plan_change->tracker_ids_that_can_be_planned);
        self::assertSame($can_possibly_prioritize_ugroups, $plan_change->can_possibly_prioritize_ugroups);
        self::assertSame($plan_iteration_change, $plan_change->iteration);
    }
}
