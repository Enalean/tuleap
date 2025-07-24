<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProjectAProgramOrUsedInPlanStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CachedProgramBuilderTest extends TestCase
{
    private const PROJECT_ID = 233;
    private const USER_ID    = 171;
    private UserIdentifierStub $user_identifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_identifier = UserIdentifierStub::withId(self::USER_ID);
    }

    private function getChecker(CheckProjectAccessStub $access_checker): CachedProgramBuilder
    {
        $program_adapter = new ProgramAdapter(
            RetrieveFullProjectStub::withProject(
                ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()
            ),
            $access_checker,
            VerifyIsProgramStub::withValidProgram(),
            RetrieveUserStub::withUser(
                UserTestBuilder::aUser()->withId(self::USER_ID)->build()
            ),
            VerifyIsProjectAProgramOrUsedInPlanStub::withValidProgram()
        );
        return new CachedProgramBuilder($program_adapter, $program_adapter);
    }

    public function testItUsesCacheToLimitDBQueriesToCheckAccessToAProgram(): void
    {
        $access_checker = CheckProjectAccessStub::withValidAccess();
        $checker        = $this->getChecker($access_checker);

        $checker->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier);
        $checker->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier);
        $checker->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier);

        self::assertSame(1, $access_checker->getCallCount());
    }

    public function testItUsesCacheToLimitDBQueriesToCheckAdminAccessToAProgram(): void
    {
        $access_checker = CheckProjectAccessStub::withValidAccess();
        $checker        = $this->getChecker($access_checker);

        $checker->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier);
        $checker->ensureProjectIsAProgramOrIsPartOfPlan(self::PROJECT_ID, $this->user_identifier);
        $checker->ensureProgramIsAProject(self::PROJECT_ID, $this->user_identifier);

        self::assertSame(1, $access_checker->getCallCount());
    }
}
