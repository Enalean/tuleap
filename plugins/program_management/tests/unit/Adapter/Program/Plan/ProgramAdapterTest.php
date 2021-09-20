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
    use GlobalLanguageMock;

    private const PROJECT_ID = 101;

    private VerifyIsProgram $program_verifier;
    private RetrieveUserStub $user_retriever;
    private UserIdentifierStub $user_identifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectAccessChecker
     */
    private $project_access_checker;

    protected function setUp(): void
    {
        $this->project_manager        = $this->createMock(\ProjectManager::class);
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
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
        $this->project_manager->method('getProject')->with(self::PROJECT_ID)->willReturn($project);
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);
    }

    public function testItSucceedWhenProgramIsAProject(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->method('getProject')->with(self::PROJECT_ID)->willReturn($project);
        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);

        $this->expectNotToPerformAssertions();
    }

    public function testItSucceedsWithBypassEvenWhenUserCannotAccessProject(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->method('getProject')->willReturn($project);
        $this->project_access_checker->method('checkUserCanAccessProject')
            ->willThrowException(new \Project_AccessPrivateException());

        $this->getAdapter()->ensureProgramIsAProject(
            self::PROJECT_ID,
            $this->user_identifier,
            new WorkflowUserPermissionBypass()
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsWithBypassWhenProjectIsNotAProgram(): void
    {
        $project = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->project_manager->method('getProject')->willReturn($project);
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(
            self::PROJECT_ID,
            $this->user_identifier,
            new WorkflowUserPermissionBypass()
        );
    }
}
