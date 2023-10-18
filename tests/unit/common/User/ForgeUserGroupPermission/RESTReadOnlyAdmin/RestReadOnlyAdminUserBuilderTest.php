<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin;

use PFUser;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use User_ForgeUserGroupPermissionsManager;

final class RestReadOnlyAdminUserBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }

    public function testItReturnsARestReadOnlyAdminUser(): void
    {
        $forge_ugroup_permissions_manager = $this->createMock(User_ForgeUserGroupPermissionsManager::class);
        $forge_ugroup_permissions_manager->method('doesUserHavePermission')->willReturn(true);

        $user = UserTestBuilder::anActiveUser()->build();

        $_SERVER['REQUEST_METHOD'] = 'get';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        self::assertInstanceOf(
            RestReadOnlyAdminUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }

    public function testItReturnsAPFUserIfRequestMethodIsNeitherGetNorOptions(): void
    {
        $forge_ugroup_permissions_manager = $this->createMock(User_ForgeUserGroupPermissionsManager::class);

        $user = UserTestBuilder::anActiveUser()->build();

        $_SERVER['REQUEST_METHOD'] = 'post';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        self::assertInstanceOf(
            PFUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }

    public function testItReturnsAPFUserIfProvidedUserIsAnonymous(): void
    {
        $forge_ugroup_permissions_manager = $this->createMock(User_ForgeUserGroupPermissionsManager::class);

        $user = UserTestBuilder::anAnonymousUser()->build();

        $_SERVER['REQUEST_METHOD'] = 'get';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        self::assertInstanceOf(
            PFUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }

    public function testItReturnsAPFUserIfProvidedUserDoesNotHaveTheDelegationOfPermission(): void
    {
        $forge_ugroup_permissions_manager = $this->createMock(User_ForgeUserGroupPermissionsManager::class);
        $forge_ugroup_permissions_manager->method('doesUserHavePermission')->willReturn(false);

        $user = UserTestBuilder::anActiveUser()->build();

        $_SERVER['REQUEST_METHOD'] = 'get';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        self::assertInstanceOf(
            PFUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }
}
