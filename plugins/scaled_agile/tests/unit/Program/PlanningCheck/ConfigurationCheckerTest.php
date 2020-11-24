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

namespace Tuleap\ScaledAgile\Program\Backlog\PlanningCheck;

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;

final class ConfigurationCheckerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildAProgramIncrementTracker(): void
    {
        $adapter = Mockery::mock(BuildPlanningConfiguration::class);
        $checker = new ConfigurationChecker($adapter);

        $program = new Program(1);
        $program_increment = new ProgramIncrementTracker(100);
        $adapter->shouldReceive('buildProgramFromTeamProject')->andReturn($program);
        $adapter->shouldReceive('buildProgramIncrementFromProjectId')->andReturn($program_increment);

        $user = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 1]);

        $this->assertEquals($program_increment, $checker->getProgramIncrementTracker($user, $project));
    }
}
