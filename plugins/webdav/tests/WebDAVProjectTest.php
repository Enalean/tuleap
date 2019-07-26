<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__.'/bootstrap.php';

/**
 * This is the unit test of WebDAVProject
 */
class WebDAVProjectTest extends TuleapTestCase {

    /**
     * Testing when The project have no active services
     */
    function testGetChildrenNoServices() {
        $webDAVProject = \Mockery::mock(\WebDAVProject::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVProject->shouldReceive('getUtils')->andReturns($utils);
        $em = \Mockery::spy(\EventManager::class);
        $utils->shouldReceive('getEventManager')->andReturns($em);

        $webDAVProject->shouldReceive('usesFile')->andReturns(false);
        $this->assertEqual($webDAVProject->getChildren(), array());

    }

    /**
     * Testing when the user can't access to the service
     */
    /*function testGetChildrenFRSActive() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $this->assertEqual($webDAVProject->getChildren(), array());

    }*/

    /**
     * Testing when the service doesn't exist
     */
    function testGetChildFailWithNotExist() {
        $webDAVProject = \Mockery::mock(\WebDAVProject::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVProject->shouldReceive('getUtils')->andReturns($utils);
        $em = \Mockery::spy(\EventManager::class);
        $utils->shouldReceive('getEventManager')->andReturns($em);

        $webDAVProject->shouldReceive('usesFile')->andReturns(false);
        $this->expectException('Sabre_DAV_Exception_FileNotFound');
        $webDAVProject->getChild('Files');

    }

    function testUserCanReadWithAccessCheckerSuccessfull()
    {
        $user =  \Mockery::spy(PFUser::class);
        $project = \Mockery::spy(Project::class);

        $access_checker = \Mockery::mock(\Tuleap\Project\ProjectAccessChecker::class);

        $webDAVProject = \Mockery::mock(
            \WebDAVProject::class,
            [
                $user,
                $project,
                0,
                $access_checker
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $access_checker->shouldReceive('checkUserCanAccessProject')->with($user, $project)->once();

        $this->assertTrue($webDAVProject->userCanRead());
    }

    function testUserCanReadWithAccessCheckerThrowingException()
    {
        $user =  \Mockery::spy(PFUser::class);
        $project = \Mockery::spy(Project::class);

        $access_checker = \Mockery::mock(\Tuleap\Project\ProjectAccessChecker::class);

        $webDAVProject = \Mockery::mock(
            \WebDAVProject::class,
            [
                $user,
                $project,
                0,
                $access_checker
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $access_checker->shouldReceive('checkUserCanAccessProject')->with($user, $project)->andThrow(new Project_AccessPrivateException());

        $this->assertFalse($webDAVProject->userCanRead());
    }
}
