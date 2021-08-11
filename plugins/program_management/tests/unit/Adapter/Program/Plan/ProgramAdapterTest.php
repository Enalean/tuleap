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
use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    private const PROJECT_ID = 101;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    private VerifyIsProgram $program_verifier;
    private RetrieveUserStub $user_retriever;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->project_manager        = \Mockery::mock(\ProjectManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);
        $this->program_verifier       = VerifyIsProgramStub::withValidProgram();
        $user                         = UserTestBuilder::aUser()->build();
        $this->user_retriever         = RetrieveUserStub::withUser($user);
        $this->user_identifier        = UserIdentifierStub::buildGenericUser();
    }

    private function getAdapter(): ProgramAdapter
    {
        return new ProgramAdapter(
            $this->project_manager,
            $this->project_access_checker,
            $this->program_verifier,
            $this->user_retriever
        );
    }

    public function testItThrowsErrorWhenProjectIsNotAProgram(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with(self::PROJECT_ID)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);
    }

    public function testItSucceedWhenProgramIsAProject(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->with(self::PROJECT_ID)->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');

        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);
    }

    public function testItSucceedsWithBypassEvenWhenUserCannotAccessProject(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->andThrow(new \Project_AccessPrivateException());

        $this->getAdapter()->ensureProgramIsAProject(
            self::PROJECT_ID,
            $this->user_identifier,
            new WorkflowUserPermissionBypass()
        );
    }

    public function testItThrowsWithBypassWhenProjectIsNotAProgram(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(
            self::PROJECT_ID,
            $this->user_identifier,
            new WorkflowUserPermissionBypass()
        );
    }
}
