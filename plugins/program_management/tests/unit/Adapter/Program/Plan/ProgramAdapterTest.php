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
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Project\ProjectAccessChecker;

final class ProgramAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    private VerifyIsProgram $program_verifier;
    private VerifyIsTeamStub $team_verifier;

    protected function setUp(): void
    {
        $this->project_manager        = \Mockery::mock(\ProjectManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);
        $this->program_verifier       = VerifyIsProgramStub::withValidProgram();
        $this->team_verifier          = VerifyIsTeamStub::withNotValidTeam();
    }

    private function getAdapter(RetrieveUser $retrieve_user): ProgramAdapter
    {
        return new ProgramAdapter(
            $this->project_manager,
            $this->project_access_checker,
            $this->program_verifier,
            $retrieve_user
        );
    }

    public function testItThrowsErrorWhenProjectIsNotAProgram(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $user                 = $this->createMock(\PFUser::class);
        $user_manager_adapter = RetrieveUserStub::buildMockedRegularUser($user);
        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter($user_manager_adapter)->ensureProgramIsAProject(
            $project_id,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItSucceedWhenProgramIsAProject(): void
    {
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

        $user                 = $this->createMock(\PFUser::class);
        $user_manager_adapter = RetrieveUserStub::buildMockedRegularUser($user);
        $this->getAdapter($user_manager_adapter)->ensureProgramIsAProject(
            $project_id,
            UserIdentifierStub::buildGenericUser()
        );
    }
}
