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

use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProjectAProgramOrUsedInPlanStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID = 101;
    private const USER_ID    = 135;
    private VerifyIsProgram $program_verifier;
    private UserIdentifier $user_identifier;
    private CheckProjectAccessStub $project_access_checker;
    private VerifyIsProjectAProgramOrUsedInPlanStub $verify_is_program_in_administration;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_access_checker              = CheckProjectAccessStub::withValidAccess();
        $this->program_verifier                    = VerifyIsProgramStub::withValidProgram();
        $this->verify_is_program_in_administration = VerifyIsProjectAProgramOrUsedInPlanStub::withValidProgram();

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
            $this->verify_is_program_in_administration
        );
    }

    public function testItAllowsAccessToProgram(): void
    {
        $this->expectNotToPerformAssertions();
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier);
    }

    public function testItThrowsErrorWhenUserDoesNotHaveAccess(): void
    {
        $this->project_access_checker = CheckProjectAccessStub::withPrivateProjectWithoutAccess();

        $this->expectException(ProgramAccessException::class);
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier);
    }

    public function testItThrowsWhenProjectIsNotAProgram(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier);
    }

    public function testItAllowsAdminAccessToProgram(): void
    {
        $this->expectNotToPerformAssertions();
        $this->getAdapter()->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier);
    }

    public function testItThrowsErrorWhenAdminCannotAccessProject(): void
    {
        $this->project_access_checker = CheckProjectAccessStub::withSuspendedProject();

        $this->expectException(ProgramAccessException::class);
        $this->getAdapter()->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier);
    }

    public function testItThrowsWhenProjectForAdminIsNotAProgram(): void
    {
        $this->verify_is_program_in_administration = VerifyIsProjectAProgramOrUsedInPlanStub::withNotValidProgram();

        $this->expectException(ProjectIsNotAProgramException::class);
        $this->getAdapter()->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier);
    }
}
