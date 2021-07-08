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

namespace Tuleap\ProgramManagement\Adapter\Team;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectManager;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Team\Creation\Team;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamAccessException;
use Tuleap\ProgramManagement\Domain\Team\TeamMustHaveExplicitBacklogEnabledException;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    private VerifyIsProgram $program_verifier;

    protected function setUp(): void
    {
        $this->project_manager      = \Mockery::mock(ProjectManager::class);
        $this->explicit_backlog_dao = \Mockery::mock(ExplicitBacklogDao::class);
        $this->program_verifier     = VerifyIsProgramStub::withValidProgram();

        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdmin(): void
    {
        $team_id = 202;
        $project = new \Project(['group_id' => $team_id, 'status' => 'A', 'access' => 'public']);
        $program = ToBeCreatedProgram::fromId(BuildProgramStub::stubValidToBeCreatedProgram(), 101, UserTestBuilder::aUser()->build());
        $user    = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($team_id)->andReturnFalse();
        $user->shouldReceive('isMember')->with($team_id)->andReturnFalse();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnFalse();
        $user->shouldReceive('isRestricted')->andReturnFalse();

        $this->project_manager->shouldReceive('getProject')->with($team_id)->once()->andReturn($project);

        $this->expectException(TeamAccessException::class);
        $this->getAdapter()->buildTeamProject([$team_id], $program, $user);
    }

    public function testItThrowExceptionWhenTeamProjectIsAlreadyAProgram(): void
    {
        $team_id = 202;
        $project = new \Project(['group_id' => $team_id, 'status' => 'A', 'access' => 'public']);
        $program = ToBeCreatedProgram::fromId(BuildProgramStub::stubValidToBeCreatedProgram(), 101, UserTestBuilder::aUser()->build());
        $user    = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($team_id)->andReturnTrue();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnTrue();

        $this->project_manager->shouldReceive('getProject')->with($team_id)->once()->andReturn($project);

        $this->expectException(ProjectIsAProgramException::class);
        $this->getAdapter()->buildTeamProject([$team_id], $program, $user);
    }

    public function testGetEmptyTeamWhenNoTeamIsFound(): void
    {
        $program = ToBeCreatedProgram::fromId(BuildProgramStub::stubValidToBeCreatedProgram(), 101, UserTestBuilder::aUser()->build());
        $user    = \Mockery::mock(\PFUser::class);

        $team_collection = $this->getAdapter()->buildTeamProject([], $program, $user);

        self::assertCount(0, $team_collection->getTeamIds());
    }

    public function testThrowsExceptionWhenTeamProjectDoesNotHaveTheExplicitBacklogModeEnabled(): void
    {
        $team_id = 202;
        $project = new \Project(['group_id' => $team_id, 'status' => 'A', 'access' => 'public']);
        $program = ToBeCreatedProgram::fromId(BuildProgramStub::stubValidToBeCreatedProgram(), 101, UserTestBuilder::aUser()->build());
        $user    = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->andReturnTrue();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnTrue();

        $this->project_manager->shouldReceive('getProject')->with($team_id)->once()->andReturn($project);
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(false);

        $this->expectException(TeamMustHaveExplicitBacklogEnabledException::class);
        $this->getAdapter()->buildTeamProject([$team_id], $program, $user);
    }

    public function testItBuildTeamCollection(): void
    {
        $team_id = 202;
        $project = new \Project(['group_id' => $team_id, 'status' => 'A', 'access' => 'public']);
        $program = ToBeCreatedProgram::fromId(BuildProgramStub::stubValidToBeCreatedProgram(), 101, UserTestBuilder::aUser()->build());
        $user    = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($team_id)->andReturnTrue();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnTrue();

        $this->project_manager->shouldReceive('getProject')->with($team_id)->andReturn($project);
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturn(true);

        $adapter         = $this->getAdapter();
        $team_collection = new TeamCollection([Team::build($adapter, $team_id, $user)], $program);

        self::assertEquals($team_collection, $adapter->buildTeamProject([$team_id], $program, $user));
    }

    private function getAdapter(): TeamAdapter
    {
        return new TeamAdapter($this->project_manager, $this->program_verifier, $this->explicit_backlog_dao);
    }
}
