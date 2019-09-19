<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

Mock::generatePartial('Rule_ProjectName', 'Rule_ProjectNameIntegration', array('_getProjectManager', '_getUserManager', '_getBackend', 'isNameAvailable', '_getSystemEventManager'));

Mock::generate('Backend');
Mock::generate('UserManager');
Mock::generate('ProjectManager');
Mock::generate('SystemEventManager');

class Rule_ProjectNameIntegrationTest extends TuleapTestCase
{

    function testValidNamesAreValid()
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
        $sm->setReturnValue('isProjectNameAvailable', true);

        $r = new Rule_ProjectNameIntegration($this);
        $r->setReturnValue('_getUserManager', $um);
        $r->setReturnValue('_getProjectManager', $pm);
        $r->setReturnValue('_getBackend', $backend);
        $r->setReturnValue('_getSystemEventManager', $sm);

        $r->setReturnValue('isNameAvailable', true, array ("group-test"));
        $r->setReturnValue('isNameAvailable', true, array ("test"));
        $r->setReturnValue('isNameAvailable', true, array ("test1"));

        $this->assertTrue($r->isValid("group-test"));
        $this->assertTrue($r->isValid("test"));
        $this->assertTrue($r->isValid("test1"));
    }
}
