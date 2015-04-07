<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2009.
 * 
 * This file is a part of Codendi.
 * 
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../include/LDAP_DirectorySynchronization.class.php';
require_once dirname(__FILE__).'/../include/LDAP_SyncReminderNotificationManager.class.php';
require_once 'common/language/BaseLanguage.class.php';
require_once 'common/user/UserManager.class.php';
require_once dirname(__FILE__).'/../include/LDAP_CleanUpManager.class.php';

// Needed because of bad type checking in SimpleTest
Mock::generatePartial('LDAP', 'MockInhLDAP', array('search', 'getErrno', 'getLDAPParam'));
Mock::generatePartial('LDAP_DirectorySynchronization', 'LDAP_DirectorySynchronizationTestVersion', array('getUserManager', 'getLdapUserManager', 'getLdapUserSync', 'getLdapSyncNotificationManager','getCleanUpManager', 'getLdapSyncReminderNotificationManager'));
Mock::generate('LDAPResultIterator');
Mock::generate('LDAPResult');
Mock::generate('BaseLanguage');
Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('LDAP_UserManager');
Mock::generate('LDAP_UserSync');
Mock::generate('LDAP_CleanUpManager');
Mock::generate('LDAP_SyncReminderNotificationManager');
Mock::generate('LDAP_SyncNotificationManager');

// Ensure user is suspended
class MyUmMock4Suspended extends MockUserManager {
    private $_myUmTest;
    function __construct($test) {
        $this->_myUmTest = $test;
        parent::__construct($test);
    }

    function updateDb($user) {
        $this->_myUmTest->assertEqual($user->getStatus(), 'S');
        $this->_myUmTest->assertEqual($user->getUnixStatus(), 'D');
        // For expectations
        parent::updateDb($user);
    }
}

class LDAP_DirectorySynchronizationTest extends TuleapTestCase {

    private $previous_log_dir;

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getContent', dirname(__FILE__).'/empty.txt');
        $this->previous_log_dir = ForgeConfig::get('codendi_log');
        ForgeConfig::set('codendi_log', '/tmp');
        ForgeConfig::set('sys_logger_level', 'debug');
    }

    function tearDown() {
        ForgeConfig::restore();
        ForgeConfig::set('codendi_log', $this->previous_log_dir);
        unset($GLOBALS['Language']);
    }

    function testNoDBUpdateIfLdapSearchFalse() {
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);

        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', 0);
        $ldap->setReturnValue('search', false);
        $ldap->expectCallCount('search', 3);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MockUserManager($this);
        $um->expectNever('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        $sync->setReturnValue('getLdapUserManager', $lum);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $lus = new MockLDAP_UserSync($this);
        $lus->expectNever('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $sync->ldapSync(array('ldap_id' => 'ed1234'), 1);
    }

    function testNoDBUpdateIfLdapSearchErrno() {
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);

        $lri = new MockLDAPResultIterator($this);
        $lri->expectNever('valid');
        $lri->expectNever('current');
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', 15);
        $ldap->setReturnReference('search', $lri);
        $ldap->expectCallCount('search', 3);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MockUserManager($this);
        $um->expectNever('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        $sync->setReturnValue('getLdapUserManager', $lum);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $lus = new MockLDAP_UserSync($this);
        $lus->expectNever('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $sync->ldapSync(array('ldap_id' => 'ed1234'), 1);
    }

    function testUserSuspendedIfNotInLDAP() {
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        ForgeConfig::set('codendi_log', '/tmp');

        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValue('valid', false);
        $lri->setReturnValue('count', 0);
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        $ldap->expectCallCount('search', 3);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MyUmMock4Suspended($this);
        $um->expectOnce('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        $sync->setReturnValue('getLdapUserManager', $lum);

        $syncNotifManager = new MockLDAP_SyncNotificationManager($this);
        $sync->setReturnValue('getLdapSyncNotificationManager', $syncNotifManager);

        $clm = new MockLDAP_CleanUpManager($this);
        $clm->expectOnce('addUserDeletionForecastDate');
        $sync->setReturnValue('getCleanUpManager', $clm);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $lus = new MockLDAP_UserSync($this);
        $lus->expectNever('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $sync->ldapSync(array('ldap_id' => 'ed1234'), 1);
    }

    function testUserLdapUidUpdateIfLdapDoesntMatch() {
        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'oula la'
                     );
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);

        $res = new MockLDAPResult($this);
        $res->setReturnValue('getLogin', 'mis_1234');

        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValue('count', 1);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);

        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        $ldap->expectCallCount('search', 1);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MockUserManager($this);
        $um->expectNever('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectOnce('updateLdapUid', array(new PFUser($row), 'mis_1234'));
        $sync->setReturnValue('getLdapUserManager', $lum);

        $syncNotifManager = new MockLDAP_SyncNotificationManager($this);
        $sync->setReturnValue('getLdapSyncNotificationManager', $syncNotifManager);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $lus = new MockLDAP_UserSync($this);
        $lus->setReturnValue('sync', false);
        $lus->expectOnce('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $sync->ldapSync($row, 1);
    }

    function testUserUpdateIfUserTellsSo() {
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);

        $res = new MockLDAPResult($this);
        $res->setReturnValue('getLogin', 'mis_1234');

        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValue('count', 1);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);

        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        $ldap->expectCallCount('search', 1);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MockUserManager($this);
        $um->expectOnce('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        $sync->setReturnValue('getLdapUserManager', $lum);

        $syncNotifManager = new MockLDAP_SyncNotificationManager($this);
        $sync->setReturnValue('getLdapSyncNotificationManager', $syncNotifManager);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $lus = new MockLDAP_UserSync($this);
        $lus->setReturnValue('sync', true);
        $lus->expectOnce('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'mis_1234'
                     );
        $sync->ldapSync($row, 1);
    }

    function testUserNoUpdateIfNothingChangedInLdap() {
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);

        $res = new MockLDAPResult($this);
        $res->setReturnValue('getLogin', 'mis_1234');

        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValue('count', 1);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);

        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        $ldap->expectCallCount('search', 1);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MockUserManager($this);
        $um->expectNever('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        $sync->setReturnValue('getLdapUserManager', $lum);

        $lus = new MockLDAP_UserSync($this);
        $lus->setReturnValue('sync', false);
        $lus->expectOnce('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'mis_1234'
                     );
        $sync->ldapSync($row, 1);
    }

    function testUserInSecondBranch() {
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);

        $res = new MockLDAPResult($this);
        $res->setReturnValue('getLogin', 'mis_1234');

        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValue('count', 1);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);

        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $param1 = 'ou=People,dc=st,dc=com ';
        $param2 = ' ou=Intranet,dc=st,dc=com ';
        $ldap->setReturnValueAt(0, 'search', false);
        $ldap->setReturnValueAt(1, 'search', $lri);
        $ldap->setReturnValueAt(2, 'search', false);
        $ldap->expectCallCount('search', 2);
        $ldap->setReturnValue('getLDAPParam', 'ou=People,dc=st,dc=com ; ou=Intranet,dc=st,dc=com ; ou=Extranet,dc=st,dc=com');
        $sync->__construct($ldap, mock('TruncateLevelLogger'));

        $um = new MockUserManager($this);
        $um->expectNever('updateDb');
        $sync->setReturnValue('getUserManager', $um);

        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        $sync->setReturnValue('getLdapUserManager', $lum);

        $lus = new MockLDAP_UserSync($this);
        $lus->setReturnValue('sync', false);
        $lus->expectOnce('sync');
        $sync->setReturnValue('getLdapUserSync', $lus);

        $syncReminderManager = new MockLDAP_SyncReminderNotificationManager($this);
        $sync->setReturnValue('getLdapSyncReminderNotificationManager', $syncReminderManager);

        $row = array('user_id'  => '4321',
                     'ldap_id'  => 'ed1234',
                     'ldap_uid' => 'mis_1234'
                     );
        $sync->ldapSync($row, 1);
    }

}
?>
