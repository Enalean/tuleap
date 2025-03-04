<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserIsProgramAdminVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsTrueWhenUserIsProgramAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $privileges_retriever = new UserIsProgramAdminVerifier(
            RetrieveUserStub::withUser(UserTestBuilder::aUser()
                ->withId(666)
                ->withoutSiteAdministrator()
                ->withAdministratorOf($project)
                ->build())
        );

        self::assertTrue(
            $privileges_retriever->isUserProgramAdmin(
                UserIdentifierStub::withId(666),
                ProgramIdentifierBuilder::buildWithId(101)
            )
        );
    }

    public function testItReturnsFalseWhenUserIsNotProgramAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $privileges_retriever = new UserIsProgramAdminVerifier(
            RetrieveUserStub::withUser(UserTestBuilder::aUser()
                ->withId(666)
                ->withoutSiteAdministrator()
                ->withMemberOf($project)
                ->build())
        );

        self::assertFalse(
            $privileges_retriever->isUserProgramAdmin(
                UserIdentifierStub::withId(666),
                ProgramIdentifierBuilder::buildWithId(101)
            )
        );
    }
}
