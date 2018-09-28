<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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
use Tuleap\User\ForgeUserGroupPermission\RetrieveSystemEventsInformationApi;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermission;
use Tuleap\User\ForgeUserGroupPermission\UserForgeUGroupPresenter;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;
use TuleapTestCase;
use User_ForgeUGroup;
use User_ForgeUserGroupPermission_ProjectApproval;
use User_ForgeUserGroupPermission_RetrieveUserMembershipInformation;
use User_ForgeUserGroupPermission_UserManagement;
use User_ForgeUserGroupPermissionsDao;

class User_ForgeUserGroupFactory_GetPermissionsForForgeUserGroupTest extends TuleapTestCase // @codingStandardsIgnoreLine
{
    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    protected $dao;

    /**
     * @var User_ForgeUserGroupPermissionsFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->dao     = mock('User_ForgeUserGroupPermissionsDao');
        $this->factory = new User_ForgeUserGroupPermissionsFactory($this->dao, mock('EventManager'));
    }

    public function itReturnsEmptyArrayIfNoResultsInDb()
    {
        $user_group = new User_ForgeUGroup(101, '', '');

        stub($this->dao)->getPermissionsForForgeUGroup(101)->returns(false);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        $this->assertEqual(0, count($all));
    }

    public function itReturnsAnArrayOfDistinctPermissions()
    {
        $user_group  = new User_ForgeUGroup(101, '', '');
        $expected_id = User_ForgeUserGroupPermission_ProjectApproval::ID;

        $permission_ids = array (
            array('permission_id' => $expected_id),
            array('permission_id' => $expected_id)
        );

        stub($this->dao)->getPermissionsForForgeUGroup(101)->returns($permission_ids);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        $this->assertCount($all, 1);

        $permission = $all[0];
        $this->assertIsA($permission, 'User_ForgeUserGroupPermission_ProjectApproval');
        $this->assertEqual($expected_id, $permission->getId());
    }

    public function itReturnsEmptyArrayIfAllForgeUserGroupHasAllPermissions()
    {
        $user_group   = new UserForgeUGroupPresenter(new User_ForgeUGroup(101, '', ''), true);
        $expected_id1 = User_ForgeUserGroupPermission_ProjectApproval::ID;
        $expected_id4 = User_ForgeUserGroupPermission_RetrieveUserMembershipInformation::ID;
        $expected_id5 = User_ForgeUserGroupPermission_UserManagement::ID;
        $expected_id6 = RetrieveSystemEventsInformationApi::ID;
        $expected_id7 = SiteAdministratorPermission::ID;
        $expected_id8 = RestProjectManagementPermission::ID;

        $permission_ids = [
            ['permission_id' => $expected_id1],
            ['permission_id' => $expected_id4],
            ['permission_id' => $expected_id5],
            ['permission_id' => $expected_id6],
            ['permission_id' => $expected_id7],
            ['permission_id' => $expected_id8]
        ];

        stub($this->dao)->getPermissionsForForgeUGroup(101)->returns($permission_ids);
        $all = $this->factory->getAllUnusedForgePermissionsForForgeUserGroup($user_group);

        $this->assertEqual(0, count($all));
    }

    public function itReturnsArrayIfAllForgeUserGroupHasNoPermission()
    {
        $user_group      = new UserForgeUGroupPresenter(new User_ForgeUGroup(101, '', ''), true);

        stub($this->dao)->getPermissionsForForgeUGroup(101)->returns(false);
        $all = $this->factory->getAllUnusedForgePermissionsForForgeUserGroup($user_group);

        $available_permissions = $this->factory->getAllAvailableForgePermissions();
        $this->assertEqual(count($available_permissions), count($all));
    }
}
