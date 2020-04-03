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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use User_ForgeUserGroupPermissionsManager;

class RestReadOnlyAdminUserBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }

    public function testItReturnsARestReadOnlyAdminUser()
    {
        $forge_ugroup_permissions_manager = Mockery::mock(User_ForgeUserGroupPermissionsManager::class);
        $forge_ugroup_permissions_manager->shouldReceive('doesUserHavePermission')->andReturnTrue();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('toRow')->andReturn([]);

        $_SERVER['REQUEST_METHOD'] = 'get';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        $this->assertInstanceOf(
            RestReadOnlyAdminUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }

    public function testItReturnsAPFUserIfRequestMethodIsNeitherGetNorOptions()
    {
        $forge_ugroup_permissions_manager = Mockery::mock(User_ForgeUserGroupPermissionsManager::class);

        $user = Mockery::mock(PFUser::class);

        $_SERVER['REQUEST_METHOD'] = 'post';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        $this->assertInstanceOf(
            PFUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }

    public function testItReturnsAPFUserIfProvidedUserIsAnonymous()
    {
        $forge_ugroup_permissions_manager = Mockery::mock(User_ForgeUserGroupPermissionsManager::class);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturnTrue();

        $_SERVER['REQUEST_METHOD'] = 'get';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        $this->assertInstanceOf(
            PFUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }

    public function testItReturnsAPFUserIfProvidedUserDoesNotHaveTheDelegationOfPermission()
    {
        $forge_ugroup_permissions_manager = Mockery::mock(User_ForgeUserGroupPermissionsManager::class);
        $forge_ugroup_permissions_manager->shouldReceive('doesUserHavePermission')->andReturnFalse();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturnFalse();
        $user->shouldReceive('toRow')->andReturn([]);

        $_SERVER['REQUEST_METHOD'] = 'get';

        $builder = new RestReadOnlyAdminUserBuilder($forge_ugroup_permissions_manager);

        $this->assertInstanceOf(
            PFUser::class,
            $builder->buildReadOnlyAdminUser($user)
        );
    }
}
