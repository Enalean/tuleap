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
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_ID = 101;

    private VerifyIsProgram $program_verifier;
    private RetrieveUserStub $user_retriever;
    private UserIdentifier $user_identifier;
    private RetrieveFullProject $retrieve_full_project;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectAccessChecker
     */
    private $project_access_checker;

    protected function setUp(): void
    {
        $this->retrieve_full_project  = RetrieveFullProjectStub::withProject(ProjectTestBuilder::aProject()->build());
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->program_verifier       = VerifyIsProgramStub::withValidProgram();
        $user                         = UserTestBuilder::aUser()->build();
        $this->user_retriever         = RetrieveUserStub::withUser($user);
        $this->user_identifier        = UserReferenceStub::withDefaults();
        $project                      = new \Project(['group_id' => self::PROJECT_ID, 'status' => 'A']);
        $this->retrieve_full_project  = RetrieveFullProjectStub::withProject($project);
    }

    private function getAdapter(): ProgramAdapter
    {
        return new ProgramAdapter(
            $this->retrieve_full_project,
            $this->project_access_checker,
            $this->program_verifier,
            $this->user_retriever
        );
    }

    public function testItThrowsErrorWhenProjectIsNotAProgram(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);
    }

    public function testItSucceedWhenProgramIsAProject(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);

        $this->expectNotToPerformAssertions();
    }

    public function testItSucceedsWithBypassEvenWhenUserCannotAccessProject(): void
    {
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
