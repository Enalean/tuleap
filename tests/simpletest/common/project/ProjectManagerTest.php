<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('common/project/ProjectManager.class.php');

Mock::generatePartial('ProjectManager', 'ProjectManagerTestVersion', array('createProjectInstance', '_getDao', '_getUserManager'));
Mock::generatePartial('ProjectManager', 'ProjectManagerTestVersion2', array('getProject', 'getProjectByUnixName', 'checkRestrictedAccess'));
Mock::generate('Project');
require_once('common/dao/ProjectDao.class.php');
Mock::generate('ProjectDao');
Mock::generate('UserManager');
Mock::generate('PFUser');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class ProjectManagerTest extends TuleapTestCase {

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage();
    }

    function tearDown() {
        unset($GLOBALS['sys_allow_restricted_users']);
        unset($GLOBALS['Language']);
    }

    function testGetProject() {
        $p1 = new MockProject($this);
        $p1->setReturnValue('getId', '1');
        $p1->setReturnValue('getUnixName', 'one');
        $p2 = new MockProject($this);
        $p2->setReturnValue('getId', '2');
        $p2->setReturnValue('getUnixName', 'two');

        $p = new ProjectManagerTestVersion($this);
        $p->setReturnValueAt(0, 'createProjectInstance', $p1);
        $p->setReturnValueAt(1, 'createProjectInstance', $p2);

        $o1 = $p->getProject(1);
        $o2 = $p->getProject(1);
        $o3 = $p->getProject(2);
        $this->assertReference($o1, $o2);
        $this->assertNotEqual($o1, $o3);
    }
    /**/
    function testClear() {
        $p1 = new MockProject($this);
        $p1->setReturnValue('getId', '1');
        $p1->setReturnValue('getUnixName', 'one');
        $p2 = new MockProject($this);
        $p2->setReturnValue('getId', '2');
        $p2->setReturnValue('getUnixName', 'two');

        $p = new ProjectManagerTestVersion($this);
        $p->setReturnValueAt(0, 'createProjectInstance', $p1);
        $p->setReturnValueAt(1, 'createProjectInstance', $p2);
        $p->setReturnValueAt(2, 'createProjectInstance', $p1);
        $p->expectAt(0, 'createProjectInstance', array(1));
        $p->expectAt(1, 'createProjectInstance', array(2));
        $p->expectAt(2, 'createProjectInstance', array(1));
        
        $p->getProject(1);
        $p->getProject(1);
        $p->getProject(2);
        $p->getProject(1);
        $p->clear(1);
        $p->getProject(1);
    }
    /**/
    
    function testRename() {
        $p1 = new MockProject($this);
        $p1->setReturnValue('getId', '1');
        $p1->setReturnValue('getUnixName', 'one');

        $pm = new ProjectManagerTestVersion($this);
        $pm->setReturnValue('createProjectInstance', $p1);
        $pm->getProject(1);

        $dao = new MockProjectDao($this);
        $dao->setReturnValue('renameProject', true, array($p1, 'TestProj'));
        $pm->setReturnValue('_getDao', $dao);

        $this->assertTrue($pm->renameProject($p1, 'TestProj'));
        $this->assertFalse($pm->isCached($p1->getId()));
        
    }

    function testGetGroupByIdForSoapNoProject() {
        if (!defined('get_group_fault')) {
            define('get_group_fault', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $pm->setReturnValue('getProject', null);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapProjectError() {
        if (!defined('get_group_fault')) {
            define('get_group_fault', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', true);
        $pm->setReturnValue('getProject', $project);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapProjectNotActive() {
        if (!defined('get_group_fault')) {
            define('get_group_fault', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', false);
        $pm->setReturnValue('getProject', $project);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapRestricted() {
        if (!defined('get_group_fault')) {
            define('get_group_fault', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', true);
        $pm->setReturnValue('getProject', $project);
        $pm->setReturnValue('checkRestrictedAccess', false);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapPass() {
        if (!defined('get_group_fault')) {
            define('get_group_fault', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', true);
        $pm->setReturnValue('getProject', $project);
        $pm->setReturnValue('checkRestrictedAccess', true);
        
        $pm->getGroupByIdForSoap(1, '');
    }

    function testCheckRestrictedAccessNoRestricted () {
        $pm = new ProjectManagerTestVersion();
        $this->assertTrue($pm->checkRestrictedAccess(null));
        $pm->expectNever('_getUserManager');
    }

    function testCheckRestrictedAccessRestrictedNotAllowed () {
        $GLOBALS['sys_allow_restricted_users'] = 0;
        $pm = new ProjectManagerTestVersion();
        $this->assertTrue($pm->checkRestrictedAccess(null));
        $pm->expectNever('_getUserManager');
    }

    function testCheckRestrictedAccessNoGroup () {
        $GLOBALS['sys_allow_restricted_users'] = 1;
        $pm = new ProjectManagerTestVersion();
        $this->assertFalse($pm->checkRestrictedAccess(null));
        $pm->expectNever('_getUserManager');
    }

    function testCheckRestrictedAccessNoUser () {
        $GLOBALS['sys_allow_restricted_users'] = 1;
        $pm = new ProjectManagerTestVersion();
        $um = new MockUserManager();
        $um->setReturnValue('getCurrentUser', null);
        $pm->setReturnValue('_getUserManager', $um);
        $project = new MockProject();
        $this->assertFalse($pm->checkRestrictedAccess($project));
        $pm->expectOnce('_getUserManager');
    }

    function testCheckRestrictedAccessUserNotRestricted () {
        $GLOBALS['sys_allow_restricted_users'] = 1;
        $pm = new ProjectManagerTestVersion();
        $um = new MockUserManager();
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', false);
        $um->setReturnValue('getCurrentUser', $user);
        $pm->setReturnValue('_getUserManager', $um);
        $project = new MockProject();
        $this->assertTrue($pm->checkRestrictedAccess($project));
        $pm->expectOnce('_getUserManager');
    }

    function testCheckRestrictedAccessUserNotMember () {
        $GLOBALS['sys_allow_restricted_users'] = 1;
        $pm = new ProjectManagerTestVersion();
        $um = new MockUserManager();
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', true);
        $um->setReturnValue('getCurrentUser', $user);
        $pm->setReturnValue('_getUserManager', $um);
        $project = new MockProject();
        $project->setReturnValue('userIsMember', false);
        $this->assertFalse($pm->checkRestrictedAccess($project));
        $pm->expectOnce('_getUserManager');
    }

    function testCheckRestrictedAccessUserIsMember () {
        $GLOBALS['sys_allow_restricted_users'] = 1;
        $pm = new ProjectManagerTestVersion();
        $um = new MockUserManager();
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', true);
        $um->setReturnValue('getCurrentUser', $user);
        $pm->setReturnValue('_getUserManager', $um);
        $project = new MockProject();
        $project->setReturnValue('userIsMember', true);
        $this->assertTrue($pm->checkRestrictedAccess($project));
        $pm->expectOnce('_getUserManager');
    }
}
?>
