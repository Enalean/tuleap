<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserPermissionsProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIdentifier $program_identifier;

    protected function setUp(): void
    {
        $this->program_identifier = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            100,
            UserProxy::buildFromPFUser(UserTestBuilder::aUser()->build())
        );
    }

    public function testItBuildsASuperAdmin(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);
        $user->method('isAdmin')->willReturn(true);

        $user_permissions = UserPermissionsProxy::buildFromPFUser($user, $this->program_identifier);
        self::assertTrue($user_permissions->isPlatformAdmin());
        self::assertTrue($user_permissions->isProjectAdmin());
    }

    public function testItBuildsAProjectAdmin(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('isAdmin')->willReturn(true);

        $user_permissions = UserPermissionsProxy::buildFromPFUser($user, $this->program_identifier);

        self::assertFalse($user_permissions->isPlatformAdmin());
        self::assertTrue($user_permissions->isProjectAdmin());
    }

    public function testItBuildARegularUser(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('isAdmin')->willReturn(false);

        $user_permissions = UserPermissionsProxy::buildFromPFUser($user, $this->program_identifier);

        self::assertFalse($user_permissions->isPlatformAdmin());
        self::assertFalse($user_permissions->isProjectAdmin());
    }
}
