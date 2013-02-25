<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/../include/AdminDelegation_UserServiceManager.class.php';

Mock::generatePartial('AdminDelegation_UserServiceManager',
                      'AdminDelegation_UserServiceManagerTestVersion',
                      array('_getUserServiceLogDao',
                            '_getUserServiceDao'));

Mock::generate('AdminDelegation_UserServiceDao');
Mock::generate('AdminDelegation_UserServiceLogDao');

require_once 'common/user/User.class.php';
Mock::generate('PFUser');

class AdminDelegation_UserServiceTest extends UnitTestCase {
    protected $_keepCurrentTime;

    function __construct($name = 'AdminDelegation_UserService test') {
        parent::__construct($name);
    }

    function setUp() {
        $this->_keepCurrentTime  = $_SERVER['REQUEST_TIME'];
        $_SERVER['REQUEST_TIME'] = 1259333681;
    }
    
    function tearDown() {
        $_SERVER['REQUEST_TIME'] = $this->_keepCurrentTime;
    }
    
    function testAddUserToPrivilegeList() {
        $usDao  = new MockAdminDelegation_UserServiceDao($this);
        $usDao->expectOnce('addUserService', array(112, AdminDelegation_Service::SHOW_PROJECT_ADMINS));
        $usDao->setReturnValue('addUserService', true);

        $uslDao = new MockAdminDelegation_UserServiceLogDao($this);
        $uslDao->expectOnce('addLog', array('grant', AdminDelegation_Service::SHOW_PROJECT_ADMINS, 112, 1259333681));

        $usm = new AdminDelegation_UserServiceManagerTestVersion($this);
        $usm->setReturnValue('_getUserServiceDao', $usDao);
        $usm->setReturnValue('_getUserServiceLogDao', $uslDao);
        
        $user = mock('PFUser');
        $user->setReturnValue('getId', 112);
        
        $usm->addUserService($user, AdminDelegation_Service::SHOW_PROJECT_ADMINS);
    }
    
    function testRevokeUserFromPrivilegeList() {
        $usDao  = new MockAdminDelegation_UserServiceDao($this);
        $usDao->expectOnce('removeUserService', array(112, AdminDelegation_Service::SHOW_PROJECT_ADMINS));
        $usDao->setReturnValue('removeUserService', true);

        $uslDao = new MockAdminDelegation_UserServiceLogDao($this);
        $uslDao->expectOnce('addLog', array('revoke', AdminDelegation_Service::SHOW_PROJECT_ADMINS, 112, 1259333681));

        $usm = new AdminDelegation_UserServiceManagerTestVersion($this);
        $usm->setReturnValue('_getUserServiceDao', $usDao);
        $usm->setReturnValue('_getUserServiceLogDao', $uslDao);
        
        $user = mock('PFUser');
        $user->setReturnValue('getId', 112);
        
        $usm->removeUserService($user, AdminDelegation_Service::SHOW_PROJECT_ADMINS);
    }
}

?>