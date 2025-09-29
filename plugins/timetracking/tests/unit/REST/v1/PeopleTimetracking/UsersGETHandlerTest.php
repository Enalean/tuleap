<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\PeopleTimetracking;

use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Test\Stubs\User\ForgePermissionsRetrieverStub;
use Tuleap\Timetracking\Tests\Stub\ProvideViewableUsersForManagerStub;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use User_ForgeUserGroupPermission;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UsersGETHandlerTest extends TestCase
{
    public function testItReturnsFaultIfManagerIsAnonymous(): void
    {
        $manager = UserTestBuilder::anAnonymousUser()->build();

        $handler = new UsersGETHandler(
            ProvideUserAvatarUrlStub::build(),
            ProvideViewableUsersForManagerStub::withMatchingUsers(UserTestBuilder::aUser()->build()),
            $this->createMock(\UserManager::class),
            ForgePermissionsRetrieverStub::withoutPermission(),
        );

        $result = $handler->handle('bob', $manager);
        self::assertTrue(Result::isErr($result));
    }

    public function testItSearchOnTheWholePlatformIfManagerIsSuperUser(): void
    {
        $manager = UserTestBuilder::anActiveUser()->withSiteAdministrator()->build();

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getPaginatedUsersByUsernameOrRealname')
            ->with('bob', false, 0, 10)
            ->willReturn(
                new \PaginatedUserCollection(
                    [
                        UserTestBuilder::aUser()->build(),
                        UserTestBuilder::aUser()->build(),
                    ],
                    2
                )
            );

        $handler = new UsersGETHandler(
            ProvideUserAvatarUrlStub::build(),
            ProvideViewableUsersForManagerStub::withNoMatchingUser(),
            $user_manager,
            ForgePermissionsRetrieverStub::withoutPermission(),
        );

        $result = $handler->handle('bob', $manager);
        self::assertTrue(Result::isOk($result));
        self::assertCount(2, $result->value);
    }

    #[TestWith([new TrackerAdminAllProjects()])]
    #[TestWith([new RestReadOnlyAdminPermission()])]
    public function testItSearchOnTheWholePlatformIfManagerHasPermissionDelegation(User_ForgeUserGroupPermission $permission): void
    {
        $manager = UserTestBuilder::anActiveUser()->withoutSiteAdministrator()->build();

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getPaginatedUsersByUsernameOrRealname')
            ->with('bob', false, 0, 10)
            ->willReturn(
                new \PaginatedUserCollection(
                    [
                        UserTestBuilder::aUser()->build(),
                        UserTestBuilder::aUser()->build(),
                    ],
                    2
                )
            );

        $handler = new UsersGETHandler(
            ProvideUserAvatarUrlStub::build(),
            ProvideViewableUsersForManagerStub::withNoMatchingUser(),
            $user_manager,
            ForgePermissionsRetrieverStub::withPermission($permission),
        );

        $result = $handler->handle('bob', $manager);
        self::assertTrue(Result::isOk($result));
        self::assertCount(2, $result->value);
    }

    public function testItSearchUserForMereMortalManager(): void
    {
        $manager = UserTestBuilder::anActiveUser()->withoutSiteAdministrator()->build();

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->expects($this->never())->method('getPaginatedUsersByUsernameOrRealname');

        $handler = new UsersGETHandler(
            ProvideUserAvatarUrlStub::build(),
            ProvideViewableUsersForManagerStub::withMatchingUsers(
                UserTestBuilder::aUser()->build(),
                UserTestBuilder::aUser()->build(),
            ),
            $user_manager,
            ForgePermissionsRetrieverStub::withoutPermission(),
        );

        $result = $handler->handle('bob', $manager);
        self::assertTrue(Result::isOk($result));
        self::assertCount(2, $result->value);
    }
}
