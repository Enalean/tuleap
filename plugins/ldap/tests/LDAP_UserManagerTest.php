<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2008.
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

require_once(dirname(__FILE__).'/../include/LDAP_UserManager.class.php');

Mock::generatePartial('LDAP_UserManager', 'LDAP_UserManagerGenerateLogin', array('getLoginFromString', 'userNameIsAvailable'));
Mock::generate('LDAP');
Mock::generate('LDAP_UserDao');
Mock::generate('PFUser');
Mock::generate('BackendSVN');
Mock::generate('SystemEventManager');

class LDAP_UserManagerTest extends UnitTestCase {

    function testGetLoginFromString() {
        $ldap = new MockLDAP($this);
        $lum = new LDAP_UserManager($ldap);
        
        $this->assertEqual($lum->getLoginFromString('coincoin'), 'coincoin');
        
        $this->assertEqual($lum->getLoginFromString('coin coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin.coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin:coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin;coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin,coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin?coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin%coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin^coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin*coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin(coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin)coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin{coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin}coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin[coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin]coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin<coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin>coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin+coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin=coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin$coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin\ coin'), 'coin_coin');
        
        $this->assertEqual($lum->getLoginFromString("coincoin'"), 'coincoin');
        $this->assertEqual($lum->getLoginFromString('coincoin"'), 'coincoin');
        $this->assertEqual($lum->getLoginFromString('coin/coin'), 'coincoin');
        
        // Accent test
        $this->assertEqual($lum->getLoginFromString('coiné'), 'coine');

        // getLoginFromString only accept utf8 strings.
        //$this->assertEqual($lum->getLoginFromString(utf8_decode('coiné')), 'coine');
    }
    
    function testGenerateLoginNotAlreadyUsed() {
        $lum = new LDAP_UserManagerGenerateLogin($this);
        
        $lum->setReturnValue('getLoginFromString', 'john');
        $lum->setReturnValue('userNameIsAvailable', true);
        
        $this->assertEqual($lum->generateLogin('john'), 'john');
    }
    
    function testGenerateLoginAlreadyUsed() {
        $lum = new LDAP_UserManagerGenerateLogin($this);
        
        $lum->setReturnValue('getLoginFromString', 'john');
        $lum->setReturnValueAt(0, 'userNameIsAvailable', false);
        $lum->setReturnValueAt(1, 'userNameIsAvailable', true);
        
        $this->assertEqual($lum->generateLogin('john'), 'john2');
    }
    
    function testUpdateLdapUidShouldPrepareRenameOfUserInTheWholePlatform() {
        // Parameters
        $user = mock('PFUser');
        $user->setReturnValue('getId', 105);
        $ldap_uid = 'johndoe';
        
        $lum  = TestHelper::getPartialMock('LDAP_UserManager', array('getDao', 'getBackendSVN'));
        
        $dao = new MockLDAP_UserDao();
        $dao->expectOnce('updateLdapUid', array(105, $ldap_uid));
        $dao->setReturnValue('updateLdapUid', true);
        $lum->setReturnValue('getDao', $dao);
        
        $this->assertTrue($lum->updateLdapUid($user, $ldap_uid));
        $this->assertEqual($lum->getUsersToRename(), array($user));
    }
    
    function testTriggerRenameOfUsersShouldUpdateSVNAccessFileOfProjectWhereTheUserIsMember() {
        // Parameters
        $user = mock('PFUser');
        $user->setReturnValue('getId', 105);
        
        $lum  = TestHelper::getPartialMock('LDAP_UserManager', array('getSystemEventManager'));
        
        $sem = new MockSystemEventManager();
        $sem->expectOnce('createEvent', array('PLUGIN_LDAP_UPDATE_LOGIN',
                                              '105',
                                              SystemEvent::PRIORITY_MEDIUM));
        $lum->setReturnValue('getSystemEventManager', $sem);
        
        $lum->addUserToRename($user);
        
        $lum->triggerRenameOfUsers();
    }
    
    function testTriggerRenameOfUsersWithSeveralUsers() {
        // Parameters
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 101);
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 102);
        $user3 = mock('PFUser');
        $user3->setReturnValue('getId', 103);
        
        $lum  = TestHelper::getPartialMock('LDAP_UserManager', array('getSystemEventManager'));
        
        $sem = new MockSystemEventManager();
        $sem->expectOnce('createEvent', array('PLUGIN_LDAP_UPDATE_LOGIN',
                                              '101'.SystemEvent::PARAMETER_SEPARATOR.'102'.SystemEvent::PARAMETER_SEPARATOR.'103',
                                              SystemEvent::PRIORITY_MEDIUM));
        $lum->setReturnValue('getSystemEventManager', $sem);
        
        $lum->addUserToRename($user1);
        $lum->addUserToRename($user2);
        $lum->addUserToRename($user3);
        
        $lum->triggerRenameOfUsers();
    }
    
    function testTriggerRenameOfUsersWithoutUser() {
        $lum = TestHelper::getPartialMock('LDAP_UserManager', array('getSystemEventManager'));
        
        $sem = new MockSystemEventManager();
        $sem->expectNever('createEvent');
        $lum->setReturnValue('getSystemEventManager', $sem);
        
        $lum->triggerRenameOfUsers();
    }
}
?>