<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use ForgeConfig;
use LDAP;
use PFUser;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use UserManager;

final class LDAPDirectorySynchronizationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('codendi_log', '/tmp');
        ForgeConfig::set('sys_logger_level', 'debug');
    }

    public function testNoDBUpdateIfLdapSearchFalse(): void
    {
        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(0);
        $ldap->method('search')->willReturn(false, false, true);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(\UserManager::class);
        $um->expects(self::never())->method('updateDb');
        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::never())->method('updateLdapUid');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::never())->method('sync');
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $sync->ldapSync(['ldap_id' => 'ed1234'], 1);
    }

    public function testNoDBUpdateIfLdapSearchErrno(): void
    {
        $lri = $this->createMock(\LDAPResultIterator::class);
        $lri->expects(self::never())->method('valid');
        $lri->expects(self::never())->method('current');
        $lri->method('count');
        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(15);
        $ldap->expects(self::exactly(3))->method('search')->willReturn($lri);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(\UserManager::class);
        $um->expects(self::never())->method('updateDb');
        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::never())->method('updateLdapUid');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::never())->method('sync');
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $sync->ldapSync(['ldap_id' => 'ed1234'], 1);
    }

    public function testUserSuspendedIfNotInLDAP(): void
    {
        ForgeConfig::set('codendi_log', '/tmp');

        $lri = $this->createMock(\LDAPResultIterator::class);
        $lri->method('valid')->willReturn(false);
        $lri->method('count')->willReturn(0);
        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(LDAP::ERR_SUCCESS);
        $ldap->expects(self::exactly(3))->method('search')->willReturn($lri);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapSyncNotificationManager',
                'getCleanUpManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(UserManager::class);
        $um->expects(self::once())
            ->method('updateDb')
            ->with(
                self::callback(function (PFUser $user): bool {
                    return $user->getStatus() === 'S' &&
                    $user->getUnixStatus() === 'D';
                })
            );

        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::never())->method('updateLdapUid');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $syncNotifManager = $this->createMock(\LDAP_SyncNotificationManager::class);
        $syncNotifManager->method('processNotification');
        $sync->method('getLdapSyncNotificationManager')->willReturn($syncNotifManager);

        $clm = $this->createMock(\LDAP_CleanUpManager::class);
        $clm->expects(self::once())->method('addUserDeletionForecastDate');
        $sync->method('getCleanUpManager')->willReturn($clm);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::never())->method('sync');
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $sync->ldapSync(['ldap_id' => 'ed1234'], 1);
    }

    public function testUserLdapUidUpdateIfLdapDoesntMatch(): void
    {
        $row = [
            'user_id'  => '4321',
            'ldap_id'  => 'ed1234',
            'ldap_uid' => 'oula la',
        ];

        $res = $this->createMock(\LDAPResult::class);
        $res->method('getLogin')->willReturn('mis_1234');

        $lri = $this->createMock(\LDAPResultIterator::class);
        $lri->method('count')->willReturn(1);
        $lri->method('valid')->willReturn(true, false);
        $lri->method('current')->willReturn($res);

        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(LDAP::ERR_SUCCESS);
        $ldap->expects(self::once())->method('search')->willReturn($lri);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapSyncNotificationManager',
                'getCleanUpManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(\UserManager::class);
        $um->expects(self::never())->method('updateDb');
        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::once())->method('updateLdapUid')->with(self::isInstanceOf(PFUser::class), 'mis_1234');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $syncNotifManager = $this->createMock(\LDAP_SyncNotificationManager::class);
        $sync->method('getLdapSyncNotificationManager')->willReturn($syncNotifManager);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::once())->method('sync')->willReturn(false);
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $sync->ldapSync($row, 1);
    }

    public function testUserUpdateIfUserTellsSo(): void
    {
        $res = $this->createMock(\LDAPResult::class);
        $res->method('getLogin')->willReturn('mis_1234');

        $lri = $this->createMock(\LDAPResultIterator::class);
        $lri->method('count')->willReturn(1);
        $lri->method('valid')->willReturn(true, false);
        $lri->method('current')->willReturn($res);

        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(LDAP::ERR_SUCCESS);
        $ldap->expects(self::once())->method('search')->willReturn($lri);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapSyncNotificationManager',
                'getCleanUpManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(\UserManager::class);
        $um->expects(self::once())->method('updateDb');
        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::never())->method('updateLdapUid');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $syncNotifManager = $this->createMock(\LDAP_SyncNotificationManager::class);
        $sync->method('getLdapSyncNotificationManager')->willReturn($syncNotifManager);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::once())->method('sync')->willReturn(true);
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $row = [
            'user_id'  => '4321',
            'ldap_id'  => 'ed1234',
            'ldap_uid' => 'mis_1234',
        ];
        $sync->ldapSync($row, 1);
    }

    public function testUserNoUpdateIfNothingChangedInLdap(): void
    {
        $res = $this->createMock(\LDAPResult::class);
        $res->method('getLogin')->willReturn('mis_1234');

        $lri = $this->createMock(\LDAPResultIterator::class);
        $lri->method('count')->willReturn(1);
        $lri->method('valid')->willReturn(true, false);
        $lri->method('current')->willReturn($res);

        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(LDAP::ERR_SUCCESS);
        $ldap->expects(self::once())->method('search')->willReturn($lri);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapSyncNotificationManager',
                'getCleanUpManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(\UserManager::class);
        $um->expects(self::never())->method('updateDb');
        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::never())->method('updateLdapUid');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::once())->method('sync')->willReturn(false);
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $row = [
            'user_id'  => '4321',
            'ldap_id'  => 'ed1234',
            'ldap_uid' => 'mis_1234',
        ];
        $sync->ldapSync($row, 1);
    }

    public function testUserInSecondBranch(): void
    {
        $res = $this->createMock(\LDAPResult::class);
        $res->method('getLogin')->willReturn('mis_1234');

        $lri = $this->createMock(\LDAPResultIterator::class);
        $lri->method('count')->willReturn(1);
        $lri->method('valid')->willReturn(true, false);
        $lri->method('current')->willReturn($res);

        $empty_lri = $this->createMock(\LDAPResultIterator::class);
        $empty_lri->method('count')->willReturn(0);

        $ldap = $this->createMock(\LDAP::class);
        $ldap->method('getErrno')->willReturn(LDAP::ERR_SUCCESS);
        $param1 = 'ou=People,dc=example,dc=com ';
        $param2 = ' ou=Intranet,dc=example,dc=com ';
        $ldap->method('search')->willReturn($empty_lri, $lri, $empty_lri);
        $ldap->method('getLDAPParam')->willReturn('ou=People,dc=example,dc=com ; ou=Intranet,dc=example,dc=com ; ou=Extranet,dc=example,dc=com');

        $sync = $this->getMockBuilder(\LDAP_DirectorySynchronization::class)
            ->setConstructorArgs([$ldap, new NullLogger()])
            ->onlyMethods([
                'getUserManager',
                'getLdapUserManager',
                'getLdapSyncReminderNotificationManager',
                'getLdapSyncNotificationManager',
                'getCleanUpManager',
                'getLdapUserSync',
            ])->getMock();

        $um = $this->createMock(\UserManager::class);
        $um->expects(self::never())->method('updateDb');
        $sync->method('getUserManager')->willReturn($um);

        $lum = $this->createMock(\LDAP_UserManager::class);
        $lum->expects(self::never())->method('updateLdapUid');
        $sync->method('getLdapUserManager')->willReturn($lum);

        $lus = $this->createMock(\LDAP_UserSync::class);
        $lus->expects(self::once())->method('sync')->willReturn(false);
        $lus->method('getSyncAttributes')->willReturn([]);
        $sync->method('getLdapUserSync')->willReturn($lus);

        $syncReminderManager = $this->createMock(\LDAP_SyncReminderNotificationManager::class);
        $sync->method('getLdapSyncReminderNotificationManager')->willReturn($syncReminderManager);

        $row = [
            'user_id'  => '4321',
            'ldap_id'  => 'ed1234',
            'ldap_uid' => 'mis_1234',
        ];
        $sync->ldapSync($row, 1);
    }
}
