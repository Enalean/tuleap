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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class BannerPermissionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BannerPermissionsChecker $banner_permissions_checker;

    public function setUp(): void
    {
        $this->banner_permissions_checker = new BannerPermissionsChecker();
    }

    public function testGetUpdateBannerPermissionShouldReturnNullIfUserIsNotProjectAdmin()
    {
        $user = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->build();

        $project = ProjectTestBuilder::aProject()->withId(108)->build();

        self::assertNull($this->banner_permissions_checker->getEditBannerPermission($user, $project));
    }

    public function testGetUpdateBannerPermissionShouldReturnThePermissionIfUserIsProjectAdmin()
    {
        $project = ProjectTestBuilder::aProject()->withId(108)->build();
        $user    = UserTestBuilder::aUser()
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $permission = $this->banner_permissions_checker->getEditBannerPermission($user, $project);

        self::assertInstanceOf(UserCanEditBannerPermission::class, $permission);
        self::assertEquals(108, $permission->getProject()->getID());
    }
}
