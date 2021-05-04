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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project_AccessException;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramForManagement;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var ProgramAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramStore
     */
    private $program_store;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;

    protected function setUp(): void
    {
        $this->project_manager        = \Mockery::mock(\ProjectManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);
        $this->program_store          = \Mockery::mock(ProgramStore::class);

        $this->adapter = new ProgramAdapter($this->project_manager, $this->project_access_checker, $this->program_store);
    }

    public function testThrowsAnErrorWhenUserCannotAccessTheProject(): void
    {
        $project = new \Project(['group_id' => 102, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(
            \Mockery::mock(Project_AccessException::class)
        );

        $this->expectException(ProgramAccessException::class);
        $this->adapter->buildExistingProgramProjectForManagement(102, UserTestBuilder::aUser()->build());
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdminButRequestAProgramForManagementOperations(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnFalse();

        $this->expectException(ProgramAccessException::class);
        $this->adapter->buildExistingProgramProjectForManagement($project_id, $user);
    }

    public function testItThrowsErrorWhenProjectIsNotAProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnFalse();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->adapter->buildExistingProgramProject($project_id, UserTestBuilder::aUser()->build());
    }

    public function testItBuildsAProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnTrue();
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

        $expected = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), $project_id);

        self::assertEquals($expected, $this->adapter->buildExistingProgramProject($project_id, UserTestBuilder::aUser()->build()));
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdminForNewProject(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnFalse();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnFalse();

        $this->expectException(ProgramAccessException::class);
        $this->adapter->buildNewProgramProject($project_id, $user);
    }

    public function testItBuildsANewProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnTrue();

        $expected = ToBeCreatedProgram::fromId(BuildProgramStub::stubValidToBeCreatedProgram(), $project_id, UserTestBuilder::aUser()->build());

        self::assertEquals($expected, $this->adapter->buildNewProgramProject($project_id, $user));
    }

    public function testCanObtainsAProgramToDoManagementOperations(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturn(true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturn(true);

        $expected = ProgramForManagement::fromId(BuildProgramStub::stubValidProgramForManagement(), $project_id, $user);
        self::assertEquals($expected, $this->adapter->buildExistingProgramProjectForManagement($project_id, $user));
    }

    public function testThrowErrorWhenUserCannotAccessToProjectForManagement(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(ProgramAccessException::class);
        $this->expectException(ProgramAccessException::class);

        $this->adapter->ensureProgramIsAProjectForManagement($project_id, UserTestBuilder::aUser()->build());
    }

    public function testThrowErrorWhenUserIsNotAdminOfProjectForManagement(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturn(false);

        $this->expectException(ProgramAccessException::class);

        $this->adapter->ensureProgramIsAProjectForManagement($project_id, $user);
    }

    public function testThrowErrorWhenProgramIsNotAProjectForManagement(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturn(false);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturn(true);

        $this->expectException(ProjectIsNotAProgramException::class);

        $this->adapter->ensureProgramIsAProjectForManagement($project_id, $user);
    }

    public function testSucceedWhenProgramIsAProjectForManagement(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturn(true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturn(true);

        $this->adapter->ensureProgramIsAProjectForManagement($project_id, $user);
    }
}
