<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class UserAccountValidityTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp() : void
    {
        parent::setUp();
        $GLOBALS['sys_suspend_inactive_accounts_delay']  = 60;
        $GLOBALS['sys_suspend_non_project_member_delay'] = 15;
    }

    protected function tearDown() : void
    {
        unset($GLOBALS['sys_suspend_inactive_accounts_delay'], $GLOBALS['sys_suspend_non_project_member_delay']);
        parent::tearDown();
    }

    public function testSuspendAccountDao() : void
    {
        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('isError')->andReturns(false);

        $da = \Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->shouldReceive('query')->with('UPDATE user SET status = "S", unix_status = "S" WHERE status != "D" AND (toto)', [])->once()->andReturns($dar);

        $dao = new UserDao($da);
        $dao->suspendAccount('toto');
    }

    public function testSuspendInactiveAccounts() : void
    {
        $currentDate = 1257757729;
        // 60 days in the past
        $lastValidAccess = 1252573729;

        $dao = \Mockery::spy(\UserDao::class);
        $dao->shouldReceive('suspendInactiveAccounts')->with($lastValidAccess)->once();

        $um = \Mockery::mock(\UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $um->shouldReceive('getDao')->andReturns($dao);

        $um->suspendInactiveAccounts($currentDate);
    }

    public function testSuspendExpiredAccountsDao() : void
    {
        $da = \Mockery::spy(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->shouldReceive('escapeInt')->with(1257671329)->andReturns(1257671329);

        $dao = \Mockery::mock(\UserDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->da = $da;
        $dao->shouldReceive('suspendAccount')->with('expiry_date != 0 AND expiry_date < 1257671329')->once();
        $dao->suspendExpiredAccounts(1257671329);
    }

    public function testSuspendUserNotProjectMembers() : void
    {
        $currentDate = 1257757729;
        // 15 days in the past
        $lastValidAccess = 1256461729;

        $dao = \Mockery::spy(\UserDao::class);
        $dao->shouldReceive('suspendUserNotProjectMembers')->with($lastValidAccess)->once();

        $um = \Mockery::mock(\UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $um->shouldReceive('getDao')->andReturns($dao);

        $um->suspendUserNotProjectMembers($currentDate);
    }

    public function testSuspendUserRemovedFromAllProjects() : void
    {
        $darUser = TestHelper::arrayToDar(array('user_id' => 112));

        $dao = \Mockery::mock(\UserDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('returnNotProjectMembers')->once()->andReturns($darUser);
        $dao->shouldReceive('getLogger')->andReturns(\Mockery::spy(\BackendLogger::class));

        $darRemovedDate = TestHelper::arrayToDar(array('date' => 1258107747));
        $dao->shouldReceive('delayForBeingNotProjectMembers')->with(112)->once()->andReturns($darRemovedDate);

        $da = \Mockery::spy(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->shouldReceive('escapeInt')->with(112)->andReturns(112);
        $dao->da = $da;

        $dao->shouldReceive('suspendAccount')->with('user.user_id = 112')->once();
        $dao->suspendUserNotProjectMembers(1258307747);
    }

    public function testRemovedFromAllProjectsDelayNotExpired() : void
    {
        $darUser = TestHelper::arrayToDar(array('user_id' => 112));

        $dao = \Mockery::mock(\UserDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('returnNotProjectMembers')->once()->andReturns($darUser);
        $dao->shouldReceive('getLogger')->andReturns(\Mockery::spy(\BackendLogger::class));

        $darRemovedDate = TestHelper::arrayToDar(array('date' => 1258407747));

        $dao->shouldReceive('delayForBeingNotProjectMembers')->with(112)->once();
        $dao->shouldReceive('delayForBeingNotProjectMembers')->with(112)->andReturns($darRemovedDate);

        $dao->shouldReceive('suspendAccount')->never();
        $dao->suspendUserNotProjectMembers(1258307747);
    }

    public function testSuspendUserNotAddedToAnyProject() : void
    {
        $darUser = TestHelper::arrayToDar(array('user_id' => 112));

        $dao = \Mockery::mock(\UserDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('returnNotProjectMembers')->once()->andReturns($darUser);
        $dao->shouldReceive('getLogger')->andReturns(\Mockery::spy(\BackendLogger::class));

        $darNewMember = TestHelper::emptyDar();

        $dao->shouldReceive('delayForBeingNotProjectMembers')->with(112)->once()->andReturns($darNewMember);

        $darAddDate = TestHelper::arrayToDar([]);

        $dao->shouldReceive('delayForBeingSubscribed')->with(112, 1258307747)->once()->andReturns($darAddDate);

        $da = \Mockery::spy(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->shouldReceive('escapeInt')->with(112)->andReturns(112);
        $dao->da = $da;

        $dao->shouldReceive('suspendAccount')->with('user.user_id = 112')->once();
        $dao->suspendUserNotProjectMembers(1258307747);
    }

    public function testNotAddedToAnyProjectDelayNotExpired() : void
    {
        $darUser = TestHelper::arrayToDar(array('user_id' => 112));

        $dao = \Mockery::mock(\UserDao::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('returnNotProjectMembers')->once()->andReturns($darUser);
        $dao->shouldReceive('getLogger')->andReturns(\Mockery::spy(\BackendLogger::class));

        $darNewMember = TestHelper::emptyDar();

        $dao->shouldReceive('delayForBeingNotProjectMembers')->with(112)->once()->andReturns($darNewMember);

        $darAddDate = TestHelper::emptyDar();
        $dao->shouldReceive('delayForBeingSubscribed')->with(112, 1258307747)->once()->andReturns($darAddDate);

        $dao->shouldReceive('suspendAccount')->never();
        $dao->suspendUserNotProjectMembers(1258307747);
    }
}
