<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;

final class SiteAdministratorPermissionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SiteAdministratorPermissionChecker $permission_checker;
    private \User_ForgeUserGroupPermissionsDao&MockObject $permission_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permission_dao     = $this->createMock(\User_ForgeUserGroupPermissionsDao::class);
        $this->permission_checker = new SiteAdministratorPermissionChecker($this->permission_dao);
    }

    public function testItReturnsFalseWhenPlatformHasOnlyOneSiteAdministrationPermission(): void
    {
        $this->permission_dao->method('isMoreThanOneUgroupUsingForgePermission')->willReturn(false);

        self::assertFalse($this->permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission());
    }

    public function testItReturnsTrueWhenPlatformHasSeveralSiteAdministrationPermission(): void
    {
        $this->permission_dao->method('isMoreThanOneUgroupUsingForgePermission')->willReturn(true);

        self::assertTrue($this->permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission());
    }

    public function testItReturnsTrueWhenPlatformHasOnlyAUGroupContainingSiteAdminsitrationPermission(): void
    {
        $ugroup = new \User_ForgeUGroup(101, 'ugroup', '');

        $this->permission_dao->method('isUGroupTheOnlyOneWithPlatformAdministrationPermission')->willReturn(false);

        self::assertTrue($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($ugroup));
    }

    public function testItReturnsFalseWhenPlatformHasSeveralAUGroupContainingSiteAdminsitrationPermission(): void
    {
        $ugroup = new \User_ForgeUGroup(101, 'ugroup', '');

        $this->permission_dao->method('isUGroupTheOnlyOneWithPlatformAdministrationPermission')->willReturn(true);

        self::assertFalse($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($ugroup));
    }
}
