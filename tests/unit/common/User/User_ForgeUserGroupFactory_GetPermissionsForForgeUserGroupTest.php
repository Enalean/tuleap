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

use Tuleap\Test\PHPUnit\TestCase;
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

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class User_ForgeUserGroupFactory_GetPermissionsForForgeUserGroupTest extends TestCase
{
    /**
     * @var User_ForgeUserGroupPermissionsDao&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dao;
    protected User_ForgeUserGroupPermissionsFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao     = $this->createMock(\User_ForgeUserGroupPermissionsDao::class);
        $event_manager = $this->createMock(\EventManager::class);

        $event_manager->method('processEvent');

        $this->factory = new User_ForgeUserGroupPermissionsFactory($this->dao, $event_manager);
    }

    public function testItReturnsEmptyArrayIfNoResultsInDb(): void
    {
        $user_group = new User_ForgeUGroup(101, '', '');

        $this->dao->method('getPermissionsForForgeUGroup')->with(101)->willReturn(false);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        self::assertEquals(0, count($all));
    }

    public function testItReturnsAnArrayOfDistinctPermissions(): void
    {
        $user_group  = new User_ForgeUGroup(101, '', '');
        $expected_id = User_ForgeUserGroupPermission_ProjectApproval::ID;

        $permission_ids =  [
            ['permission_id' => $expected_id],
            ['permission_id' => $expected_id],
        ];

        $this->dao->method('getPermissionsForForgeUGroup')->with(101)->willReturn($permission_ids);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        self::assertCount(1, $all);

        $permission = $all[0];
        self::assertInstanceOf(\User_ForgeUserGroupPermission_ProjectApproval::class, $permission);
        self::assertEquals($expected_id, $permission->getId());
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
            ['permission_id' => $expected_id9],
        ];

        $this->dao->method('getPermissionsForForgeUGroup')->with(101)->willReturn($permission_ids);
        $all = $this->factory->getAllUnusedForgePermissionsForForgeUserGroup($user_group);

        self::assertEquals(0, count($all));
    }

    public function testItReturnsArrayIfAllForgeUserGroupHasNoPermission(): void
    {
        $user_group = new UserForgeUGroupPresenter(new User_ForgeUGroup(101, '', ''), true);

        $this->dao->method('getPermissionsForForgeUGroup')->with(101)->willReturn(false);
        $all = $this->factory->getAllUnusedForgePermissionsForForgeUserGroup($user_group);

        $available_permissions = $this->factory->getAllAvailableForgePermissions();
        self::assertEquals(count($available_permissions), count($all));
    }
}
