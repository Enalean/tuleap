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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Plan;

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ConfigurationCheckerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildAProgramIncrementTracker(): void
    {
        $planning_adapter = Mockery::mock(BuildPlanProgramConfiguration::class);
        $plan_adapter     = Mockery::mock(BuildPlanProgramIncrementConfiguration::class);
        $checker          = new ConfigurationChecker($planning_adapter, $plan_adapter);

        $program           = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 1);
        $program_increment = new ProgramTracker(TrackerTestBuilder::aTracker()->build());
        $planning_adapter->shouldReceive('buildProgramIdentifierFromTeamProject')->andReturn($program);
        $plan_adapter->shouldReceive('buildTrackerProgramIncrementFromProjectId')->andReturn($program_increment);

        $user    = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 1]);

        self::assertEquals($program_increment, $checker->getProgramIncrementTracker($user, $project));
    }

    public function testItReturnsNullWhenThereIsNOProgram(): void
    {
        $planning_adapter = Mockery::mock(BuildPlanProgramConfiguration::class);
        $plan_adapter     = Mockery::mock(BuildPlanProgramIncrementConfiguration::class);
        $checker          = new ConfigurationChecker($planning_adapter, $plan_adapter);

        $planning_adapter->shouldReceive('buildProgramIdentifierFromTeamProject')->andReturn(null);

        $user    = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 1]);

        self::assertNull($checker->getProgramIncrementTracker($user, $project));
    }
}
