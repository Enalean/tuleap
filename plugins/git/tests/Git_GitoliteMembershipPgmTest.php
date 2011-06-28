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

require_once dirname(__FILE__).'/../include/Git_GitoliteMembershipPgm.class.php';

Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('UserManager');
Mock::generate('UGroupManager');

class Git_GitoliteMembershipPgmTest extends UnitTestCase {

    function getPartialMock($className, $methods) {
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName($this);
    }

    function testUserIsProjectMember() {
        $mbPgm = $this->getPartialMock('Git_GitoliteMembershipPgm', array('getUserManager', 'getProjectManager'));

        $user = new User(array('user_id' => 202, 'language_id' => 'en_US'));
        $user->setUserGroupData(array(array('group_id' => 101, 'admin_flags' => '')));
        $um = new MockUserManager($this);
        $um->expectOnce('getUserByUserName', array('john_do'));
        $um->setReturnValue('getUserByUserName', $user);
        $mbPgm->setReturnValue('getUserManager', $um);

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'gpig');
        $pm = new MockProjectManager($this);
        $pm->expectOnce('getProject', array(101));
        $pm->setReturnValue('getProject', $project);
        $mbPgm->setReturnValue('getProjectManager', $pm);

        $this->assertEqual($mbPgm->getGroups('john_do'), array('gpig_project_members'));
    }

    function testUserIsProjectAdmin() {
        $mbPgm = $this->getPartialMock('Git_GitoliteMembershipPgm', array('getUserManager', 'getProjectManager'));

        $user = new User(array('user_id' => 202, 'language_id' => 'en_US'));
        $user->setUserGroupData(array(array('group_id' => 101, 'admin_flags' => 'A')));
        $um = new MockUserManager($this);
        $um->expectOnce('getUserByUserName', array('john_do'));
        $um->setReturnValue('getUserByUserName', $user);
        $mbPgm->setReturnValue('getUserManager', $um);

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'gpig');
        $pm = new MockProjectManager($this);
        $pm->expectOnce('getProject', array(101));
        $pm->setReturnValue('getProject', $project);
        $mbPgm->setReturnValue('getProjectManager', $pm);

        $this->assertEqual($mbPgm->getGroups('john_do'), array('gpig_project_members', 'gpig_project_admin'));
    }

    function testUserIsMemberOfAStaticUgroup() {
        $mbPgm = $this->getPartialMock('Git_GitoliteMembershipPgm', array('getUserManager', 'getProjectManager', 'getUGroupManager'));

        $user = new User(array('user_id' => 202, 'language_id' => 'en_US'));
        $user->setUserGroupData(array());
        $um = new MockUserManager($this);
        $um->expectOnce('getUserByUserName', array('john_do'));
        $um->setReturnValue('getUserByUserName', $user);
        $mbPgm->setReturnValue('getUserManager', $um);

        $pm = new MockProjectManager($this);
        $pm->expectNever('getProject');
        $mbPgm->setReturnValue('getProjectManager', $pm);

        $ugm = new MockUGroupManager($this);
        $ugm->setReturnValue('getByUserId', array(array('ugroup_id' => 304)));
        $mbPgm->setReturnValue('getUGroupManager', $ugm);

        $this->assertEqual($mbPgm->getGroups('john_do'), array('ug_304'));
    }
}
?>