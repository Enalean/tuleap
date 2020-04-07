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

declare(strict_types=1);

namespace Tuleap\WebDAV;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessPrivateException;
use Sabre_DAV_Exception_FileNotFound;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\ProjectAccessChecker;
use WebDAVProject;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVProject
 */
final class WebDAVProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * Testing when The project have no active services
     */
    public function testGetChildrenNoServices(): void
    {
        $webDAVProject = Mockery::mock(WebDAVProject::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = Mockery::spy(\WebDAVUtils::class);
        $webDAVProject->shouldReceive('getUtils')->andReturns($utils);
        $em = Mockery::spy(\EventManager::class);
        $utils->shouldReceive('getEventManager')->andReturns($em);

        $webDAVProject->shouldReceive('usesFile')->andReturns(false);
        $this->assertSame([], $webDAVProject->getChildren());
    }

    /**
     * Testing when the service doesn't exist
     */
    public function testGetChildFailWithNotExist(): void
    {
        $webDAVProject = Mockery::mock(WebDAVProject::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = Mockery::spy(\WebDAVUtils::class);
        $webDAVProject->shouldReceive('getUtils')->andReturns($utils);
        $em = Mockery::spy(\EventManager::class);
        $utils->shouldReceive('getEventManager')->andReturns($em);

        $webDAVProject->shouldReceive('usesFile')->andReturns(false);
        $this->expectException(Sabre_DAV_Exception_FileNotFound::class);
        $webDAVProject->getChild('Files');
    }

    public function testUserCanReadWithAccessCheckerSuccessfull(): void
    {
        $user =  Mockery::spy(PFUser::class);
        $project = Mockery::spy(Project::class);

        $access_checker = Mockery::mock(ProjectAccessChecker::class);

        $webDAVProject = Mockery::mock(
            WebDAVProject::class,
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

    public function testUserCanReadWithAccessCheckerThrowingException(): void
    {
        $user =  Mockery::spy(PFUser::class);
        $project = Mockery::spy(Project::class);

        $access_checker = Mockery::mock(ProjectAccessChecker::class);

        $webDAVProject = Mockery::mock(
            WebDAVProject::class,
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
