<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessProjectNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanProgramAdapterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PlanProgramAdapter
     */
    private $adapter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\URLVerification
     */
    private $url_verification;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamStore
     */
    private $team_store;

    protected function setUp(): void
    {
        $this->project_manager  = Mockery::mock(\ProjectManager::class);
        $this->url_verification = Mockery::mock(\URLVerification::class);
        $this->team_store       = Mockery::mock(TeamStore::class);

        $this->adapter = new PlanProgramAdapter(
            $this->project_manager,
            $this->url_verification,
            $this->team_store,
            BuildProgramStub::stubValidProgram()
        );
    }

    public function testItThrowExceptionWhenUserCanNotAccessToProgram(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $project           = new Project(['group_id' => 101]);
        $team              = new Project(['group_id' => $project->getID()]);
        $program_increment = new Project(['group_id' => 200]);
        $this->project_manager->shouldReceive('getProject')->with($project->getId())->andReturn($team);
        $this->project_manager->shouldReceive('getProject')->with($program_increment->getId())->andReturn($program_increment);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn($program_increment->getID());

        $this->url_verification->shouldReceive('userCanAccessProject')->once()
            ->andThrow(Project_AccessProjectNotFoundException::class);
        $this->expectException(UserCanNotAccessToProgramException::class);
        $this->adapter->buildProgramIdentifierFromTeamProject($project, $user);
    }

    public function testItBuildAProgram(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $project           = new Project(['group_id' => 101]);
        $team              = new Project(['group_id' => $project->getID()]);
        $program_increment = new Project(['group_id' => 200]);
        $this->project_manager->shouldReceive('getProject')->with($project->getId())->andReturn($team);
        $this->project_manager->shouldReceive('getProject')->with($program_increment->getId())->andReturn($program_increment);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn($program_increment->getID());

        $this->url_verification->shouldReceive('userCanAccessProject')->once();

        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), $program_increment->getID());
        self::assertEquals($program, $this->adapter->buildProgramIdentifierFromTeamProject($project, $user));
    }

    public function testItReturnsNullWhenProgramIsNotFound(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = new Project(['group_id' => 101]);
        $team    = new Project(['group_id' => $project->getID()]);
        $this->project_manager->shouldReceive('getProject')->with($project->getId())->andReturn($team);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturnNull();

        self::assertNull($this->adapter->buildProgramIdentifierFromTeamProject($project, $user));
    }
}
