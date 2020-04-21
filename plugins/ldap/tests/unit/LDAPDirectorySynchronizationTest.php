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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use UserManager;

require_once __DIR__ . '/bootstrap.php';

class LDAPDirectorySynchronizationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var array
     */
    private $globals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globals = $GLOBALS;
        $GLOBALS = [];

        $GLOBALS['Language'] = \Mockery::spy(\BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getContent')->andReturns(dirname(__FILE__) . '/empty.txt');
        ForgeConfig::set('codendi_log', '/tmp');
        ForgeConfig::set('sys_logger_level', 'debug');
    }

    protected function tearDown(): void
    {
        $GLOBALS = $this->globals;
        parent::tearDown();
    }

    public function testNoDBUpdateIfLdapSearchFalse(): void
    {
        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(0);
        $ldap->shouldReceive('search')->andReturns(false, false, true);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');

        $sync = \Mockery::mock(
            \LDAP_DirectorySynchronization::class,
            [$ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class)]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('updateDb')->never();
        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->never();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->never();
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $sync->ldapSync(array('ldap_id' => 'ed1234'), 1);
    }

    public function testNoDBUpdateIfLdapSearchErrno(): void
    {
        $sync = \Mockery::mock(\LDAP_DirectorySynchronization::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $lri = \Mockery::spy(\LDAPResultIterator::class);
        $lri->shouldReceive('valid')->never();
        $lri->shouldReceive('current')->never();
        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(15);
        $ldap->shouldReceive('search')->times(3)->andReturns($lri);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class));

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('updateDb')->never();
        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->never();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->never();
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $sync->ldapSync(array('ldap_id' => 'ed1234'), 1);
    }

    public function testUserSuspendedIfNotInLDAP(): void
    {
        ForgeConfig::set('codendi_log', '/tmp');

        $lri = \Mockery::spy(\LDAPResultIterator::class);
        $lri->shouldReceive('valid')->andReturns(false);
        $lri->shouldReceive('count')->andReturns(0);
        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(LDAP::ERR_SUCCESS);
        $ldap->shouldReceive('search')->times(3)->andReturns($lri);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');

        $sync = \Mockery::mock(
            \LDAP_DirectorySynchronization::class,
            [$ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class)]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $um = Mockery::mock(UserManager::class);
        $um->shouldReceive('updateDb')
            ->with(
                Mockery::on(function (PFUser $user) {
                    return $user->getStatus() === 'S' &&
                    $user->getUnixStatus() === 'D';
                })
            )
            ->once();

        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->never();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $syncNotifManager = \Mockery::spy(\LDAP_SyncNotificationManager::class);
        $sync->shouldReceive('getLdapSyncNotificationManager')->andReturns($syncNotifManager);

        $clm = \Mockery::spy(\LDAP_CleanUpManager::class);
        $clm->shouldReceive('addUserDeletionForecastDate')->once();
        $sync->shouldReceive('getCleanUpManager')->andReturns($clm);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->never();
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $sync->ldapSync(array('ldap_id' => 'ed1234'), 1);
    }

    public function testUserLdapUidUpdateIfLdapDoesntMatch(): void
    {
        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'oula la'
                     );
        $sync = \Mockery::mock(\LDAP_DirectorySynchronization::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $res = \Mockery::spy(\LDAPResult::class);
        $res->shouldReceive('getLogin')->andReturns('mis_1234');

        $lri = \Mockery::spy(\LDAPResultIterator::class);
        $lri->shouldReceive('count')->andReturns(1);
        $lri->shouldReceive('valid')->andReturns(true, false);
        $lri->shouldReceive('current')->andReturns($res);

        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(LDAP::ERR_SUCCESS);
        $ldap->shouldReceive('search')->once()->andReturns($lri);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class));

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('updateDb')->never();
        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->with(Mockery::type(PFUser::class), 'mis_1234')->once();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $syncNotifManager = \Mockery::spy(\LDAP_SyncNotificationManager::class);
        $sync->shouldReceive('getLdapSyncNotificationManager')->andReturns($syncNotifManager);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->once()->andReturns(false);
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $sync->ldapSync($row, 1);
    }

    public function testUserUpdateIfUserTellsSo(): void
    {
        $sync = \Mockery::mock(\LDAP_DirectorySynchronization::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $res = \Mockery::spy(\LDAPResult::class);
        $res->shouldReceive('getLogin')->andReturns('mis_1234');

        $lri = \Mockery::spy(\LDAPResultIterator::class);
        $lri->shouldReceive('count')->andReturns(1);
        $lri->shouldReceive('valid')->andReturns(true, false);
        $lri->shouldReceive('current')->andReturns($res);

        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(LDAP::ERR_SUCCESS);
        $ldap->shouldReceive('search')->once()->andReturns($lri);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class));

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('updateDb')->once();
        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->never();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $syncNotifManager = \Mockery::spy(\LDAP_SyncNotificationManager::class);
        $sync->shouldReceive('getLdapSyncNotificationManager')->andReturns($syncNotifManager);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->once()->andReturns(true);
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'mis_1234'
                     );
        $sync->ldapSync($row, 1);
    }

    public function testUserNoUpdateIfNothingChangedInLdap(): void
    {
        $sync = \Mockery::mock(\LDAP_DirectorySynchronization::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $res = \Mockery::spy(\LDAPResult::class);
        $res->shouldReceive('getLogin')->andReturns('mis_1234');

        $lri = \Mockery::spy(\LDAPResultIterator::class);
        $lri->shouldReceive('count')->andReturns(1);
        $lri->shouldReceive('valid')->andReturns(true, false);
        $lri->shouldReceive('current')->andReturns($res);

        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(LDAP::ERR_SUCCESS);
        $ldap->shouldReceive('search')->once()->andReturns($lri);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class));

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('updateDb')->never();
        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->never();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->once()->andReturns(false);
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'mis_1234'
                     );
        $sync->ldapSync($row, 1);
    }

    public function testUserInSecondBranch(): void
    {
        $sync = \Mockery::mock(\LDAP_DirectorySynchronization::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $res = \Mockery::spy(\LDAPResult::class);
        $res->shouldReceive('getLogin')->andReturns('mis_1234');

        $lri = \Mockery::spy(\LDAPResultIterator::class);
        $lri->shouldReceive('count')->andReturns(1);
        $lri->shouldReceive('valid')->andReturns(true, false);
        $lri->shouldReceive('current')->andReturns($res);

        $empty_lri = \Mockery::spy(\LDAPResultIterator::class);
        $empty_lri->shouldReceive('count')->andReturns(0);

        $ldap = \Mockery::mock(\LDAP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ldap->shouldReceive('getErrno')->andReturns(LDAP::ERR_SUCCESS);
        $param1 = 'ou=People,dc=st,dc=com ';
        $param2 = ' ou=Intranet,dc=st,dc=com ';
        $ldap->shouldReceive('search')->andReturns($empty_lri, $lri, $empty_lri);
        $ldap->shouldReceive('getLDAPParam')->andReturns('ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, \Mockery::spy(\Psr\Log\LoggerInterface::class));

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('updateDb')->never();
        $sync->shouldReceive('getUserManager')->andReturns($um);

        $lum = \Mockery::spy(\LDAP_UserManager::class);
        $lum->shouldReceive('updateLdapUid')->never();
        $sync->shouldReceive('getLdapUserManager')->andReturns($lum);

        $lus = \Mockery::spy(\LDAP_UserSync::class);
        $lus->shouldReceive('sync')->once()->andReturns(false);
        $sync->shouldReceive('getLdapUserSync')->andReturns($lus);

        $syncReminderManager = \Mockery::spy(\LDAP_SyncReminderNotificationManager::class);
        $sync->shouldReceive('getLdapSyncReminderNotificationManager')->andReturns($syncReminderManager);

        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'mis_1234'
                     );
        $sync->ldapSync($row, 1);
    }
}
