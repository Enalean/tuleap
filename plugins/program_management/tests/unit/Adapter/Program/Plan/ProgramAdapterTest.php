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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProjectAProgramOrUsedInPlanStub;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_ID = 101;
    private const USER_ID    = 135;

    private VerifyIsProgram $program_verifier;
    private UserIdentifier $user_identifier;
    private ProjectAccessChecker & MockObject $project_access_checker;

    protected function setUp(): void
    {
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->program_verifier       = VerifyIsProgramStub::withValidProgram();

        $this->user_identifier = UserReferenceStub::withIdAndName(self::USER_ID, 'John');
    }

    private function getAdapter(): ProgramAdapter
    {
        $user = UserTestBuilder::aUser()->withId(self::USER_ID)->build();

        return new ProgramAdapter(
            RetrieveFullProjectStub::withProject(
                ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()
            ),
            $this->project_access_checker,
            $this->program_verifier,
            RetrieveUserStub::withUser($user),
            VerifyIsProjectAProgramOrUsedInPlanStub::withValidProgram()
        );
    }

    public function testItThrowsErrorWhenProjectIsNotAProgram(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);
        $this->getAdapter()->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier, null);
    }

    public function testItSucceedWhenProgramIsAProjectAndUsesCache(): void
    {
        $this->project_access_checker->expects(self::once())->method('checkUserCanAccessProject');

        $adapter = $this->getAdapter();

        $adapter->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier, null);
        $adapter->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier, null);
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
        $this->getAdapter()->ensureProjectIsAProgramOrIsPartOfPlan(
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
