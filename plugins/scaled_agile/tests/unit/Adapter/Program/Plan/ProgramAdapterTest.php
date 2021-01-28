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

namespace Tuleap\ScaledAgile\Adapter\Program\Plan;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\Program\ToBeCreatedProgram;

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

    protected function setUp(): void
    {
        $this->project_manager = \Mockery::mock(\ProjectManager::class);
        $this->program_store   = \Mockery::mock(ProgramStore::class);

        $this->adapter = new ProgramAdapter($this->project_manager, $this->program_store);

        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdmin(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnFalse();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnFalse();
        $user->shouldReceive('isMember')->with($project_id)->andReturnFalse();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnFalse();
        $user->shouldReceive('isRestricted')->andReturnFalse();

        $this->expectException(ProgramAccessException::class);
        $this->adapter->buildExistingProgramProject($project_id, $user);
    }


    public function testItThrowsErrorWhenProjectIsNotAProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnFalse();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnTrue();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnTrue();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->adapter->buildExistingProgramProject($project_id, $user);
    }

    public function testItBuildsAProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnTrue();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnTrue();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnTrue();

        $expected = new Program($project_id);

        self::assertEquals($expected, $this->adapter->buildExistingProgramProject($project_id, $user));
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdminForNewPject(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->program_store->shouldReceive('isProjectAProgramProject')->with($project_id)->andReturnFalse();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnFalse();
        $user->shouldReceive('isMember')->with($project_id)->andReturnFalse();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnFalse();
        $user->shouldReceive('isRestricted')->andReturnFalse();

        $this->expectException(ProgramAccessException::class);
        $this->adapter->buildNewProgramProject($project_id, $user);
    }

    public function testItBuildsANewProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')->with($project_id)->andReturnTrue();
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('isSuperUser')->andReturnTrue();

        $expected = new ToBeCreatedProgram($project_id);

        self::assertEquals($expected, $this->adapter->buildNewProgramProject($project_id, $user));
    }
}
