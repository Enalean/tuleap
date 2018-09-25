<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\User\ForgeUserGroupPermission;

class SiteAdministratorPermissionCheckerTest extends \TuleapTestCase
{
    /**
     * @var SiteAdministratorPermissionChecker
     */
    private $permission_checker;

    /**
     * @var \User_ForgeUserGroupPermissionsDao
     */
    private $permission_dao;

    public function setUp()
    {
        parent::setUp();

        $this->permission_dao = mock('User_ForgeUserGroupPermissionsDao');

        $this->permission_checker = new SiteAdministratorPermissionChecker($this->permission_dao);
    }

    public function itReturnsFalseWhenPlatformHasOnlyOneSiteAdministrationPermission()
    {
        stub($this->permission_dao)->isMoreThanOneUgroupUsingForgePermission()->returns(false);

        $this->assertFalse($this->permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission());
    }

    public function itReturnsTrueWhenPlatformHasSeveralSiteAdministrationPermission()
    {
        stub($this->permission_dao)->isMoreThanOneUgroupUsingForgePermission()->returns(true);

        $this->assertTrue($this->permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission());
    }

    public function itReturnsTrueWhenPlatformHasOnlyAUGroupContainingSiteAdminsitrationPermission()
    {
        $ugroup = mock('User_ForgeUGroup');
        stub($ugroup)->getId()->returns(101);
        stub($this->permission_dao)->isUGroupTheOnlyOneWithPlatformAdministrationPermission()->returns(false);

        $this->assertTrue($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($ugroup));
    }

    public function itReturnsFalseWhenPlatformHasSeveralAUGroupContainingSiteAdminsitrationPermission()
    {
        $ugroup = mock('User_ForgeUGroup');
        stub($ugroup)->getId()->returns(101);
        stub($this->permission_dao)->isUGroupTheOnlyOneWithPlatformAdministrationPermission()->returns(true);

        $this->assertFalse($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($ugroup));
    }
}
