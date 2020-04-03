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

class SiteAdministratorPermissionCheckerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var SiteAdministratorPermissionChecker
     */
    private $permission_checker;

    /**
     * @var \User_ForgeUserGroupPermissionsDao
     */
    private $permission_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permission_dao = \Mockery::spy(\User_ForgeUserGroupPermissionsDao::class);

        $this->permission_checker = new SiteAdministratorPermissionChecker($this->permission_dao);
    }

    public function testItReturnsFalseWhenPlatformHasOnlyOneSiteAdministrationPermission(): void
    {
        $this->permission_dao->shouldReceive('isMoreThanOneUgroupUsingForgePermission')->andReturns(false);

        $this->assertFalse($this->permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission());
    }

    public function testItReturnsTrueWhenPlatformHasSeveralSiteAdministrationPermission(): void
    {
        $this->permission_dao->shouldReceive('isMoreThanOneUgroupUsingForgePermission')->andReturns(true);

        $this->assertTrue($this->permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission());
    }

    public function testItReturnsTrueWhenPlatformHasOnlyAUGroupContainingSiteAdminsitrationPermission(): void
    {
        $ugroup = \Mockery::spy(\User_ForgeUGroup::class);
        $ugroup->shouldReceive('getId')->andReturns(101);
        $this->permission_dao->shouldReceive('isUGroupTheOnlyOneWithPlatformAdministrationPermission')->andReturns(false);

        $this->assertTrue($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($ugroup));
    }

    public function testItReturnsFalseWhenPlatformHasSeveralAUGroupContainingSiteAdminsitrationPermission(): void
    {
        $ugroup = \Mockery::spy(\User_ForgeUGroup::class);
        $ugroup->shouldReceive('getId')->andReturns(101);
        $this->permission_dao->shouldReceive('isUGroupTheOnlyOneWithPlatformAdministrationPermission')->andReturns(true);

        $this->assertFalse($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($ugroup));
    }
}
