<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Project\Banner;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;

class BannerPermissionsCheckerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var BannerPermissionsChecker
     */
    private $banner_permissions_checker;

    public function setUp(): void
    {
        $this->banner_permissions_checker = new BannerPermissionsChecker();
    }

    public function testGetUpdateBannerPermissionShouldReturnNullIfUserIsNotProjectAdmin()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(false);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(108);

        $this->assertNull($this->banner_permissions_checker->getEditBannerPermission($user, $project));
    }

    public function testGetUpdateBannerPermissionShouldReturnThePermissionIfUserIsProjectAdmin()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(true);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(108);

        $permission = $this->banner_permissions_checker->getEditBannerPermission($user, $project);

        $this->assertInstanceOf(UserCanEditBannerPermission::class, $permission);
        $this->assertEquals(108, $permission->getProject()->getID());
    }
}
