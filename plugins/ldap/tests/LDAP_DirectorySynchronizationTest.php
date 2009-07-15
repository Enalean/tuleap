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
require_once 'common/language/BaseLanguage.class.php';
require_once 'common/user/UserManager.class.php';

Mock::generatePartial('LDAP', 'MockInhLDAP', array('search', 'getErrno'));
Mock::generatePartial('LDAP_DirectorySynchronization', 'LDAP_DirectorySynchronizationTestVersion', array('getUserManager', 'getLdapUserManager'));
Mock::generate('LDAPResultIterator');
Mock::generate('LDAPResult');
Mock::generate('BaseLanguage');
Mock::generate('UserManager');
Mock::generate('User');
Mock::generate('LDAP_UserManager');

class LDAP_DirectorySynchronizationTest extends UnitTestCase {
    
    function __construct($name = 'LDAP_DirectorySynchronization test') {
        parent::__construct($name);
    }
    
    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getContent', dirname(__FILE__).'/empty.txt');
    }
    
    function tearDown() {
        unset($GLOBALS['Language']);
    }
    
    function testNoDBUpdateIfLdapSearchFalse() {
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', 0);
        $ldap->setReturnValue('search', false);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $sync->ldapSync(array('(eduid=ed1234)','(eduid=ed1235)'), array());
        $this->assertEqual($sync->userToUpdate, array());
    }
    
    function testNoDBUpdateIfLdapSearchErrno() {
        $lri = new MockLDAPResultIterator($this);
        $lri->expectNever('valid');
        $lri->expectNever('current');
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', 15);
        $ldap->setReturnReference('search', $lri);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $sync->ldapSync(array('(eduid=ed1234)','(eduid=ed1235)'), array());
        $this->assertEqual($sync->userToUpdate, array());
    }
    
    function testUserSuspendedIfNotInLDAP() {
        //ed1234 exists while ed1235 doesn't
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)','(eduid=ed1235)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister 1234',
                            'email' => 'mis_1234@codendi.com',
                            'ldap_uid' => 'mis_1234'),
                          'ed1235' => array(
                            'user_id' => '5321'),
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array('5321' => array('status' => 'S', 'unix_status' => 'D')));
    }
    
    function testUserUpdateEmailIfLdapDoesntMatch() {
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister 1234',
                            'email' => 'pouet@latrompette',
                            'ldap_uid' => 'mis_1234')
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array('4321' => array('email' => 'mis_1234@codendi.com')));
    }
    
    function testUserUpdateRealnameIfLdapDoesntMatch() {
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister Freeze',
                            'email' => 'mis_1234@codendi.com',
                            'ldap_uid' => 'mis_1234')
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array('4321' => array('realname' => 'Mister 1234')));
    }
    
    function testUserLdapUidUpdateIfLdapDoesntMatch() {
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister 1234',
                            'email' => 'mis_1234@codendi.com',
                            'ldap_uid' => 'oula la')
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array('4321' => array('ldap_uid' => 'mis_1234')));
    }
    
    function testChangeUserStatusWithDedicatedCode() {
        // A part of this test is in ldap_synchro.php file
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        $res->setReturnValue('get', 'contractor', array('employeetype'));
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        // Override default language
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getContent', dirname(__FILE__).'/ldap_synchro.php');
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister 1234',
                            'email' => 'mis_1234@codendi.com',
                            'ldap_uid' => 'mis_1234',
                            'status'   => 'A'),
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array('4321' => array('status' => 'R')));
    }
    
    function testNoUserStatusChangeWithDedicatedCode() {
        // A part of this test is in ldap_synchro.php file
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        $res->setReturnValue('get', 'st', array('employeetype'));
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        // Override default language
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getContent', dirname(__FILE__).'/ldap_synchro.php');
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister 1234',
                            'email' => 'mis_1234@codendi.com',
                            'ldap_uid' => 'mis_1234',
                            'status'   => 'A'),
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array());
    }
    
    function testUserNoUpdateIfNothingChangedInLdap() {
        $res = new MockLDAPResult($this);
        $res->setReturnValue('getEdUid',      'ed1234');
        $res->setReturnValue('getCommonName', 'Mister 1234');
        $res->setReturnValue('getLogin', 'mis_1234');
        $res->setReturnValue('getEmail', 'mis_1234@codendi.com');
        
        $lri = new MockLDAPResultIterator($this);
        $lri->setReturnValueAt(0, 'valid', true);
        $lri->setReturnValueAt(1, 'valid', false);
        $lri->setReturnReference('current', $res);
        
        $ldap = new MockInhLDAP($this);
        $ldap->setReturnValue('getErrno', LDAP::ERR_SUCCESS);
        $ldap->setReturnReference('search', $lri);
        
        $sync = new LDAP_DirectorySynchronization($ldap);
        $ldapChunks = array('(eduid=ed1234)');
        $userRows = array('ed1234' => array(
                            'user_id' => '4321',
                            'realname' => 'Mister 1234',
                            'email' => 'mis_1234@codendi.com',
                            'ldap_uid' => 'mis_1234')
        );
        $sync->ldapSync($ldapChunks, $userRows);
        $this->assertEqual($sync->userToUpdate, array());
    }

    //
    // Codendi database related stuff
    //    
    
    function testUpdateCodendiUserNoUserToUpdate() {
        $um = new MockUserManager($this);
        $um->expectNever('getUserById');
        $um->expectNever('updateDb');
        
        $user = new MockUser($this);
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        
        $sync->syncUsers();
    }
    
    function testUpdateCodendiUserNoAttributeToUpdate() {
        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array(1848));
        $um->expectNever('updateDb');
        
        $lum = new MockLDAP_UserManager($this);
        $lum->expectNever('updateLdapUid');
        
        $user = new MockUser($this);
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        $sync->setReturnReference('getLdapUserManager', $lum);
        
        $sync->userToUpdate[1848] = array();
        $sync->syncUsers();
    }
    
    function testUpdateCodendiUserStatus() {
        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array(1789));
        $um->expectOnce('updateDb');
        
        $user = new MockUser($this);
        $user->expectOnce('setStatus', array('R'));
        $user->expectNever('setRealName');
        $user->expectNever('setEmail');
        $user->expectNever('setUnixStatus');
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        
        $sync->userToUpdate['1789'] = array('status' => 'R');
        $sync->syncUsers();
    }
    
    function testUpdateCodendiUserRealName() {
        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array(1789));
        $um->expectOnce('updateDb');
        
        $user = new MockUser($this);
        $user->expectNever('setStatus');
        $user->expectOnce('setRealName', array('John DOE'));
        $user->expectNever('setEmail');
        $user->expectNever('setUnixStatus');
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        
        $sync->userToUpdate['1789'] = array('realname' => 'John DOE');
        $sync->syncUsers();
    }
    
    function testUpdateCodendiUserEmail() {
        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array(1789));
        $um->expectOnce('updateDb');
        
        $user = new MockUser($this);
        $user->expectNever('setStatus');
        $user->expectNever('setRealName');
        $user->expectOnce('setEmail', array('john.doe@codendi.com'));
        $user->expectNever('setUnixStatus');
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        
        $sync->userToUpdate['1789'] = array('email' => 'john.doe@codendi.com');
        $sync->syncUsers();
    }
    
    function testUpdateCodendiUnixStatus() {
        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array(1789));
        $um->expectOnce('updateDb');
        
        $user = new MockUser($this);
        $user->expectNever('setStatus');
        $user->expectNever('setRealName');
        $user->expectNever('setEmail');
        $user->expectOnce('setUnixStatus', array('2345'));
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        
        $sync->userToUpdate['1789'] = array('unix_status' => '2345');
        $sync->syncUsers();
    }
    
    function testUpdateCodendiAllAttributes() {
        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array(1789));
        $um->expectOnce('updateDb');
        
        $user = new MockUser($this);
        $user->expectOnce('setStatus', array('S'));
        $user->expectOnce('setRealName', array('The one who was fired'));
        $user->expectOnce('setEmail', array('your.new.home.is@anpe.fr'));
        $user->expectOnce('setUnixStatus', array('D'));
        $um->setReturnReference('getUserById', $user);
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        
        $sync->userToUpdate['1789'] = array('unix_status' => 'D', 'status' => 'S', 'realname' => 'The one who was fired', 'email' => 'your.new.home.is@anpe.fr');
        $sync->syncUsers();
    }
    
    function testUpdateCodendiLdapUid() {
        $um = new MockUserManager($this);
        $user = new MockUser($this);
        $um->setReturnReference('getUserById', $user);
        
        $lum = new MockLDAP_UserManager($this);
        $lum->expectOnce('updateLdapUid', array(1789, 'ed5678'));
        
        $sync = new LDAP_DirectorySynchronizationTestVersion($this);
        $sync->setReturnReference('getUserManager', $um);
        $sync->setReturnReference('getLdapUserManager', $lum);
        
        $sync->userToUpdate['1789'] = array('ldap_uid' => 'ed5678');
        $sync->syncUsers();
    }
}
?>