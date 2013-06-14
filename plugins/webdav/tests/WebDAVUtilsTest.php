<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('PFUser');
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generatePartial(
    'WebDAVUtils',
    'WebDAVUtilsTestVersion',
array()
);

/**
 * This is the unit test of WebDAVUtils
 */
class WebDAVUtilsTest extends UnitTestCase {

    /**
     * Testing when The user is not member and is not super user
     */
    function testUserIsAdminNotSuperUserNotmember() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', false);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), false);

    }

    /**
     * Testing when The user is super user
     */
    function testUserIsAdminSuperUser() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', false);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin
     */
    function testUserIsAdminGroupAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin and super user
     */
    function testUserIsAdminSuperUserGroupAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is file release admin
     */
    function testUserIsAdminFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is file release admin and super user
     */
    function testUserIsAdminSuperuserFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin and file release admin
     */
    function testUserIsAdminGroupAdminFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', true);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin filerelease admin and super user
     */
    function testUserIsAdminSuperUserGroupAdminFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', true);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

}
?>