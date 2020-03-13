<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\WebDAV;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVUtils
 */
class WebDAVUtilsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $user;
    private $project;
    private $utils;
    private $frs_permission_manager;
    private $project_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                   = \Mockery::spy(\PFUser::class);
        $this->project                = \Mockery::spy(\Project::class);
        $this->utils                  = \Mockery::mock(\WebDAVUtils::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->frs_permission_manager = \Mockery::spy(\Tuleap\FRS\FRSPermissionManager::class);
        $this->project_manager        = \Mockery::spy(\ProjectManager::class);

        $this->utils->shouldReceive('getFRSPermissionManager')->andReturn($this->frs_permission_manager);
        $this->utils->shouldReceive('getProjectManager')->andReturn($this->project_manager);

        $this->project_manager->shouldReceive('getProject')->with(101)->andReturn($this->project);
    }

    public function testUserIsAdminNotSuperUserNotmember(): void
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('isMember')->andReturns(false);
        $this->frs_permission_manager->shouldReceive('isAdmin')->with($this->project, $this->user)->andReturn(false);

        $this->assertFalse($this->utils->userIsAdmin($this->user, 101));
    }

    public function testUserIsAdminSuperUser(): void
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(true);
        $this->user->shouldReceive('isMember')->andReturns(false);

        $this->assertTrue($this->utils->userIsAdmin($this->user, 101));
    }

    public function testUserIsAdminFRSAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->frs_permission_manager->shouldReceive('isAdmin')->with($this->project, $this->user)->andReturn(true);

        $this->assertTrue($this->utils->userIsAdmin($this->user, 101));
    }

    public function testUserIsAdminSuperuserFRSAdmin(): void
    {
        $this->user->shouldReceive('isSuperUser')->andReturns(true);
        $this->frs_permission_manager->shouldReceive('isAdmin')->with($this->project, $this->user)->andReturn(true);

        $this->assertTrue($this->utils->userIsAdmin($this->user, 101));
    }
}
