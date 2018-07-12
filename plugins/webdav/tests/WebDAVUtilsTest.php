<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of tuleap.
 *
 * tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

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
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user                   = \Mockery::spy(\PFUser::class);
        $this->project                = \Mockery::spy(\Project::class);
        $this->utils                  = \Mockery::mock(\WebDAVUtils::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->frs_permission_manager = \Mockery::spy(\Tuleap\FRS\FRSPermissionManager::class);
        $this->project_manager        = \Mockery::spy(\ProjectManager::class);

        stub($this->utils)->getFRSPermissionManager()->returns($this->frs_permission_manager);
        stub($this->utils)->getProjectManager()->returns($this->project_manager);

        $this->project_manager->shouldReceive('getProject')->with(101)->andReturn($this->project);
    }

    public function testUserIsAdminNotSuperUserNotmember()
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('isMember')->andReturns(false);
        $this->frs_permission_manager->shouldReceive('isAdmin')->with($this->project, $this->user)->andReturn(false);

        $this->assertEqual($this->utils->userIsAdmin($this->user, 101), false);
    }

    public function testUserIsAdminSuperUser()
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(true);
        $this->user->shouldReceive('isMember')->andReturns(false);

        $this->assertEqual($this->utils->userIsAdmin($this->user, 101), true);
    }

    public function testUserIsAdminFRSAdmin()
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->frs_permission_manager->shouldReceive('isAdmin')->with($this->project, $this->user)->andReturn(true);

        $this->assertEqual($this->utils->userIsAdmin($this->user, 101), true);
    }

    public function testUserIsAdminSuperuserFRSAdmin()
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(true);
        $this->frs_permission_manager->shouldReceive('isAdmin')->with($this->project, $this->user)->andReturn(true);

        $this->assertEqual($this->utils->userIsAdmin($this->user, 101), true);
    }
}
