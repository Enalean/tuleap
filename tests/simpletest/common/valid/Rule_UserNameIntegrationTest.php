<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 *
 */

Mock::generatePartial('Rule_UserName', 'Rule_UserNameIntegration', array('_getProjectManager', '_getUserManager', '_getBackend', '_getSystemEventManager'));

Mock::generate('UserManager');
Mock::generate('PFUser');

Mock::generate('ProjectManager');
Mock::generate('Project');

Mock::generate('BaseLanguage');

Mock::generate('Backend');

Mock::generate('SystemEventManager');

class Rule_UserNameIntegrationTest extends TuleapTestCase
{

    function __construct($name = 'Rule_UserName Integration test')
    {
        parent::__construct($name);
    }

    function testOk()
    {
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', null);

        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', null);

        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', false);

        $sm = new MockSystemEventManager($this);
        $sm->setReturnValue('isUserNameAvailable', true);

        $r = new Rule_UserNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getBackend', $backend);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $this->assertTrue($r->isValid("user"));
        $this->assertTrue($r->isValid("user_name"));
        $this->assertTrue($r->isValid("user-name"));
    }

    function testUserAlreadyExist()
    {
        $u  = mock('PFUser');
        $um = new MockUserManager($this);
        stub($um)->getUserByUsername('user')->returns($u);

        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', null);

        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', false);

        $sm = new MockSystemEventManager($this);
        $sm->setReturnValue('isUserNameAvailable', true);

        $r = new Rule_UserNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getBackend', $backend);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $this->assertFalse($r->isValid("user"));
    }

    function testProjectAlreadyExist()
    {
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', null);

        $p  = new MockProject($this);
        $pm = new MockProjectManager($this);
        stub($pm)->getProjectByUnixName('user')->returns($p);

        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', false);

        $sm = new MockSystemEventManager($this);
        $sm->setReturnValue('isUserNameAvailable', true);

        $r = new Rule_UserNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $this->assertFalse($r->isValid("user"));
    }

    function testSpaceInName()
    {
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', null);

        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', null);

        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', false);

        $sm = new MockSystemEventManager($this);
        $sm->setReturnValue('isUserNameAvailable', true);

        $r = new Rule_UserNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getBackend', $backend);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $this->assertFalse($r->isValid("user name"));
    }

    function testUnixUserExists()
    {
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', null);

        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', null);

        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', true);
        $backend->setReturnValue('unixGroupExists', false);

        $sm = new MockSystemEventManager($this);
        $sm->setReturnValue('isUserNameAvailable', true);

        $r = new Rule_UserNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getBackend', $backend);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $this->assertFalse($r->isValid("user"));
    }

    function testUnixGroupExists()
    {
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserByUserName', null);

        $pm = new MockProjectManager($this);
        $pm->setReturnValue('getProjectByUnixName', null);

        $backend = new MockBackend($this);
        $backend->setReturnValue('unixUserExists', false);
        $backend->setReturnValue('unixGroupExists', true);

        $sm = new MockSystemEventManager($this);
        $sm->setReturnValue('isUserNameAvailable', true);

        $r = new Rule_UserNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getBackend', $backend);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $this->assertFalse($r->isValid("user"));
    }
}
