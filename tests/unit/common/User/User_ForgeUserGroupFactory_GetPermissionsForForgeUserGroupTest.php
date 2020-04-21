<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\User;

use Tuleap\User\ForgeUserGroupPermission\RestProjectManagementPermission;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use Tuleap\User\ForgeUserGroupPermission\RetrieveSystemEventsInformationApi;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermission;
use Tuleap\User\ForgeUserGroupPermission\UserForgeUGroupPresenter;
use User_ForgeUGroup;
use User_ForgeUserGroupPermission_ProjectApproval;
use User_ForgeUserGroupPermission_RetrieveUserMembershipInformation;
use User_ForgeUserGroupPermission_UserManagement;
use User_ForgeUserGroupPermissionsDao;

class User_ForgeUserGroupFactory_GetPermissionsForForgeUserGroupTest extends \PHPUnit\Framework\TestCase // @codingStandardsIgnoreLine
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    protected $dao;

    /**
     * @var User_ForgeUserGroupPermissionsFactory
     */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao     = \Mockery::spy(\User_ForgeUserGroupPermissionsDao::class);
        $this->factory = new User_ForgeUserGroupPermissionsFactory($this->dao, \Mockery::spy(\EventManager::class));
    }

    public function testItReturnsEmptyArrayIfNoResultsInDb(): void
    {
        $user_group = new User_ForgeUGroup(101, '', '');

        $this->dao->shouldReceive('getPermissionsForForgeUGroup')->with(101)->andReturns(false);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        $this->assertEquals(0, count($all));
    }

    public function testItReturnsAnArrayOfDistinctPermissions(): void
    {
        $user_group  = new User_ForgeUGroup(101, '', '');
        $expected_id = User_ForgeUserGroupPermission_ProjectApproval::ID;

        $permission_ids = array (
            array('permission_id' => $expected_id),
            array('permission_id' => $expected_id)
        );

        $this->dao->shouldReceive('getPermissionsForForgeUGroup')->with(101)->andReturns($permission_ids);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        $this->assertCount(1, $all);

        $permission = $all[0];
        $this->assertInstanceOf(\User_ForgeUserGroupPermission_ProjectApproval::class, $permission);
        $this->assertEquals($expected_id, $permission->getId());
    }

    public function testItReturnsEmptyArrayIfAllForgeUserGroupHasAllPermissions(): void
    {
        $user_group   = new UserForgeUGroupPresenter(new User_ForgeUGroup(101, '', ''), true);
        $expected_id1 = User_ForgeUserGroupPermission_ProjectApproval::ID;
        $expected_id4 = User_ForgeUserGroupPermission_RetrieveUserMembershipInformation::ID;
        $expected_id5 = User_ForgeUserGroupPermission_UserManagement::ID;
        $expected_id6 = RetrieveSystemEventsInformationApi::ID;
        $expected_id7 = SiteAdministratorPermission::ID;
        $expected_id8 = RestProjectManagementPermission::ID;
        $expected_id9 = RestReadOnlyAdminPermission::ID;

        $permission_ids = [
            ['permission_id' => $expected_id1],
            ['permission_id' => $expected_id4],
            ['permission_id' => $expected_id5],
            ['permission_id' => $expected_id6],
            ['permission_id' => $expected_id7],
            ['permission_id' => $expected_id8],
            ['permission_id' => $expected_id9]
        ];

        $this->dao->shouldReceive('getPermissionsForForgeUGroup')->with(101)->andReturns($permission_ids);
        $all = $this->factory->getAllUnusedForgePermissionsForForgeUserGroup($user_group);

        $this->assertEquals(0, count($all));
    }

    public function testItReturnsArrayIfAllForgeUserGroupHasNoPermission(): void
    {
        $user_group      = new UserForgeUGroupPresenter(new User_ForgeUGroup(101, '', ''), true);

        $this->dao->shouldReceive('getPermissionsForForgeUGroup')->with(101)->andReturns(false);
        $all = $this->factory->getAllUnusedForgePermissionsForForgeUserGroup($user_group);

        $available_permissions = $this->factory->getAllAvailableForgePermissions();
        $this->assertEquals(count($available_permissions), count($all));
    }
}
