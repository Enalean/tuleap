<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\GlobalAdmin;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;

class GlobalAdminPermissionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDoesUserHaveTrackerGlobalAdminRightsOnProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(42)->build();

        $super_user = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => false, 'isSuperUser' => true])
            ->getMock();

        $project_admin = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => true, 'isSuperUser' => false])
            ->getMock();

        $project_member = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => false, 'isSuperUser' => false])
            ->getMock();

        $user_with_special_rights = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => false, 'isSuperUser' => false])
            ->getMock();

        $forge_user_group_permissions_manager = Mockery::mock(\User_ForgeUserGroupPermissionsManager::class);
        $forge_user_group_permissions_manager
            ->shouldReceive('doesUserHavePermission')
            ->with($project_member, Mockery::type(TrackerAdminAllProjects::class))
            ->andReturnFalse();
        $forge_user_group_permissions_manager
            ->shouldReceive('doesUserHavePermission')
            ->with($user_with_special_rights, Mockery::type(TrackerAdminAllProjects::class))
            ->andReturnTrue();

        $checker = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $super_user));
        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_admin));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_member));
        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user_with_special_rights));
    }

    public function testDoesUserHaveTrackerGlobalAdminRightsOnProjectWhenProjectIsNotCreatedYet(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(42)->withStatusCreatingFromArchive()->build();

        $super_user = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => false, 'isSuperUser' => true])
            ->getMock();

        $project_admin = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => true, 'isSuperUser' => false])
            ->getMock();

        $project_member = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => false, 'isSuperUser' => false])
            ->getMock();

        $user_with_special_rights = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isAdmin' => false, 'isSuperUser' => false])
            ->getMock();

        $forge_user_group_permissions_manager = Mockery::mock(\User_ForgeUserGroupPermissionsManager::class);
        $forge_user_group_permissions_manager
            ->shouldReceive('doesUserHavePermission')
            ->with($project_member, Mockery::type(TrackerAdminAllProjects::class))
            ->andReturnFalse();
        $forge_user_group_permissions_manager
            ->shouldReceive('doesUserHavePermission')
            ->with($user_with_special_rights, Mockery::type(TrackerAdminAllProjects::class))
            ->andReturnTrue();

        $checker = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $super_user));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_admin));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_member));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user_with_special_rights));
    }

    public function testDoesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform(): void
    {
        $super_user = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isSuperUser' => true])
            ->getMock();

        $regular_user = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isSuperUser' => false])
            ->getMock();

        $user_with_special_rights = Mockery::mock(\PFUser::class)
            ->shouldReceive(['isSuperUser' => false])
            ->getMock();

        $forge_user_group_permissions_manager = Mockery::mock(\User_ForgeUserGroupPermissionsManager::class);
        $forge_user_group_permissions_manager
            ->shouldReceive('doesUserHavePermission')
            ->with($regular_user, Mockery::type(TrackerAdminAllProjects::class))
            ->andReturnFalse();
        $forge_user_group_permissions_manager
            ->shouldReceive('doesUserHavePermission')
            ->with($user_with_special_rights, Mockery::type(TrackerAdminAllProjects::class))
            ->andReturnTrue();

        $checker = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($super_user));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($regular_user));
        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($user_with_special_rights));
    }
}
