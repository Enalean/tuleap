<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once 'bootstrap.php';

Mock::generate('PFUser');
Mock::generate('Project');
Mock::generatePartial(
    'WebDAVUtils',
    'WebDAVUtilsTestVersion',
    array('getFRSPermissionManager', 'getProjectManager')
);

/**
 * This is the unit test of WebDAVUtils
 */
class WebDAVUtilsTest extends TuleapTestCase
{
    private $user;
    private $project;
    private $utils;
    private $frs_permission_manager;
    private $project_manager;

    public function setUp()
    {
        $this->user                   = mock('PFUser');
        $this->project                = new MockProject();
        $this->utils                  = new WebDAVUtilsTestVersion($this);
        $this->frs_permission_manager = mock('Tuleap\FRS\FRSPermissionManager');
        $this->project_manager        = mock('ProjectManager');

        stub($this->utils)->getFRSPermissionManager()->returns($this->frs_permission_manager);
        stub($this->utils)->getProjectManager()->returns($this->project_manager);
    }

    public function testuserIsAdminNotSuperUserNotmember()
    {
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('isMember', false);
        stub($this->frs_permission_manager)->isAdmin()->returns(false);

        $this->assertEqual($this->utils->userIsAdmin($this->user, $this->project->getGroupId()), false);
    }

    public function testuserIsAdminSuperUser()
    {
        $this->user->setReturnValue('isSuperUser', true);
        $this->user->setReturnValue('isMember', false);

        $this->assertEqual($this->utils->userIsAdmin($this->user, $this->project->getGroupId()), true);
    }

    public function testuserIsAdminFRSAdmin()
    {
        $this->user->setReturnValue('isSuperUser', false);
        stub($this->frs_permission_manager)->isAdmin()->returns(true);

        $this->assertEqual($this->utils->userIsAdmin($this->user, $this->project->getGroupId()), true);
    }

    public function testuserIsAdminSuperuserFRSAdmin()
    {
        $this->user->setReturnValue('isSuperUser', true);
        stub($this->frs_permission_manager)->isAdmin()->returns(true);

        $this->assertEqual($this->utils->userIsAdmin($this->user, $this->project->getGroupId()), true);
    }
}
