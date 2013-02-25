<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(dirname(__FILE__).'/../../include/system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php');

Mock::generate('Project');
Mock::generate('PFUser');
Mock::generate('UserManager');

class SystemEvent_PLUGIN_LDAP_UPDATE_LOGINTest extends UnitTestCase {
    
    function testUpdateShouldUpdateAllProjects() {
        $id           = 1002;
        $type         = LDAP_UserManager::EVENT_UPDATE_LOGIN;
        $parameters   = '101::102';
        $priority     = SystemEvent::PRIORITY_MEDIUM;
        $status       = SystemEvent::STATUS_RUNNING;
        $create_date  = '';
        $process_date = '';
        $end_date     = '';
        $log          = '';
        
        $se = TestHelper::getPartialMock('SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN', array('getUserManager', 'getBackendSVN', 'getProject'));
        
        $user1 = mock('PFUser');
        $user1->setReturnValue('getAllProjects', array(201, 202));
        $user1->setReturnValue('isActive', true);
        $user2 = mock('PFUser');
        $user2->setReturnValue('getAllProjects', array(202, 203));
        $user2->setReturnValue('isActive', true);
        $um = new MockUserManager();
        $um->setReturnValue('getUserById', $user1, array('101'));
        $um->setReturnValue('getUserById', $user2, array('102'));
        $se->setReturnValue('getUserManager', $um);
        
        $prj1 = new MockProject();
        $prj2 = new MockProject();
        $prj3 = new MockProject();
        $se->setReturnValue('getProject', $prj1, array(201));
        $se->setReturnValue('getProject', $prj2, array(202));
        $se->setReturnValue('getProject', $prj3, array(203));
        
        $backend = new MockBackendSVN();
        $backend->expectCallCount('updateProjectSVNAccessFile', 3);
        $backend->expect('updateProjectSVNAccessFile', array($prj1));
        $backend->expect('updateProjectSVNAccessFile', array($prj2));
        $backend->expect('updateProjectSVNAccessFile', array($prj3));
        $se->setReturnValue('getBackendSVN', $backend);
        
        $se->__construct($id, $type, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log);
        $se->process();
    }
}
?>