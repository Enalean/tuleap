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

namespace Tuleap\ProgramManagement\Domain\Team\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesAPlan(): void
    {
        $program_adapter = BuildProgramStub::stubValidToBeCreatedProgram();
        $team_adapter    = \Mockery::mock(BuildTeam::class);
        $team_adapter->shouldReceive('checkProjectIsATeam')->once();

        $project_id      = 101;
        $team_project_id = 2;

        $user = UserTestBuilder::aUser()->build();

        $program    = ToBeCreatedProgram::fromId($program_adapter, $project_id, $user);
        $collection = new TeamCollection([Team::build($team_adapter, $team_project_id, $user)], $program);
        $team_adapter->shouldReceive('buildTeamProject')
            ->with(
                [$team_project_id],
                \Mockery::on(function (ToBeCreatedProgram $to_be_created_program) use ($program) {
                    return $to_be_created_program->getId() === $program->getId();
                }),
                $user
            )->once()
            ->andReturn($collection);

        $team_dao = \Mockery::mock(TeamStore::class);
        $team_dao->shouldReceive('save')->with($collection)->once();

        $team_adapter = new TeamCreator($program_adapter, $team_adapter, $team_dao);
        $team_adapter->create($user, $project_id, [$team_project_id]);
    }
}
