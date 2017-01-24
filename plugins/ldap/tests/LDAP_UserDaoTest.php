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

require_once(dirname(__FILE__).'/../include/LDAP_UserDao.class.php');
require_once('common/dao/include/DataAccessResult.class.php');
require_once('common/dao/include/DataAccess.class.php');

Mock::generatePartial('LDAP_UserDao', 'LDAP_UserDaoTV', array('retrieve'));
Mock::generate('DataAccessResult');
Mock::generate('DataAccess');

class LDAP_UserDaoTest extends TuleapTestCase {
    
    function __construct($name = 'LDAP_UserDao test') {
        parent::__construct($name);
    }
    
    function testUserNameIsAvailable() {
        $darUser = new MockDataAccessResult($this);
        $darUser->setReturnValue('rowCount', 0);
        $darGroup = new MockDataAccessResult($this);
        $darGroup->setReturnValue('rowCount', 0);
        
        $ldapUserDao = new LDAP_UserDaoTV($this);
        $ldapUserDao->da = new MockDataAccess($this);
        $ldapUserDao->da->expectCallCount('quoteSmart', 2);
        $ldapUserDao->setReturnValueAt(0, 'retrieve', $darUser);
        $ldapUserDao->setReturnValueAt(1, 'retrieve', $darGroup);
        $ldapUserDao->expectCallCount('retrieve', 2);
        
        $this->assertTrue($ldapUserDao->userNameIsAvailable('coincoin'));
    }
    
    function testUserNameIsAlreadyUser() {
        $darUser = new MockDataAccessResult($this);
        $darUser->setReturnValue('rowCount', 1);
        
        $ldapUserDao = new LDAP_UserDaoTV($this);
        $ldapUserDao->da = new MockDataAccess($this);
        $ldapUserDao->da->expectCallCount('quoteSmart', 1);
        $ldapUserDao->setReturnValueAt(0, 'retrieve', $darUser);
        $ldapUserDao->expectCallCount('retrieve', 1);
        
        $this->assertFalse($ldapUserDao->userNameIsAvailable('coincoin'));
    }
    
    function testUserNameIsAlreadyGroup() {
        $darUser = new MockDataAccessResult($this);
        $darUser->setReturnValue('rowCount', 0);
        $darGroup = new MockDataAccessResult($this);
        $darGroup->setReturnValue('rowCount', 1);
        
        $ldapUserDao = new LDAP_UserDaoTV($this);
        $ldapUserDao->da = new MockDataAccess($this);
        $ldapUserDao->da->expectCallCount('quoteSmart', 2);
        $ldapUserDao->setReturnValueAt(0, 'retrieve', $darUser);
        $ldapUserDao->setReturnValueAt(1, 'retrieve', $darGroup);
        $ldapUserDao->expectCallCount('retrieve', 2);
        
        $this->assertFalse($ldapUserDao->userNameIsAvailable('coincoin'));
    }
    
}
?>