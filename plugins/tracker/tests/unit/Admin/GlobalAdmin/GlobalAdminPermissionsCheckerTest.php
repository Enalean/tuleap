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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use User_ForgeUserGroupPermission;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GlobalAdminPermissionsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testDoesUserHaveTrackerGlobalAdminRightsOnProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(42)->build();

        $super_user = UserTestBuilder::aUser()
            ->withoutMemberOfProjects()
            ->withSiteAdministrator()
            ->build();

        $project_admin = UserTestBuilder::aUser()
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $project_member = UserTestBuilder::aUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $user_with_special_rights = UserTestBuilder::aUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $forge_user_group_permissions_manager = $this->createMock(\User_ForgeUserGroupPermissionsManager::class);
        $forge_user_group_permissions_manager
            ->method('doesUserHavePermission')
            ->willReturnCallback(static fn(\PFUser $user, User_ForgeUserGroupPermission $permission) => match (true) {
                $user === $project_member && $permission instanceof TrackerAdminAllProjects => false,
                $user === $user_with_special_rights && $permission instanceof TrackerAdminAllProjects => true,
            });

        $checker = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $super_user));
        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_admin));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_member));
        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user_with_special_rights));
    }

    public function testDoesUserHaveTrackerGlobalAdminRightsOnProjectWhenProjectIsNotCreatedYet(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(42)->withStatusCreatingFromArchive()->build();

        $super_user = UserTestBuilder::aUser()
            ->withoutMemberOfProjects()
            ->withSiteAdministrator()
            ->build();

        $project_admin = UserTestBuilder::aUser()
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $project_member = UserTestBuilder::aUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $user_with_special_rights = UserTestBuilder::aUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $forge_user_group_permissions_manager = $this->createMock(\User_ForgeUserGroupPermissionsManager::class);
        $forge_user_group_permissions_manager
            ->method('doesUserHavePermission')
            ->willReturnCallback(static fn(\PFUser $user, User_ForgeUserGroupPermission $permission) => match (true) {
                $user === $project_member && $permission instanceof TrackerAdminAllProjects => false,
                $user === $user_with_special_rights && $permission instanceof TrackerAdminAllProjects => true,
            });

        $checker = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $super_user));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_admin));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $project_member));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user_with_special_rights));
    }

    public function testDoesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform(): void
    {
        $super_user = UserTestBuilder::buildSiteAdministrator();

        $regular_user = UserTestBuilder::anActiveUser()->build();

        $user_with_special_rights = UserTestBuilder::anActiveUser()->build();

        $forge_user_group_permissions_manager = $this->createMock(\User_ForgeUserGroupPermissionsManager::class);
        $forge_user_group_permissions_manager
            ->method('doesUserHavePermission')
            ->willReturnCallback(static fn(\PFUser $user, User_ForgeUserGroupPermission $permission) => match (true) {
                $user === $regular_user && $permission instanceof TrackerAdminAllProjects => false,
                $user === $user_with_special_rights && $permission instanceof TrackerAdminAllProjects => true,
            });

        $checker = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($super_user));
        self::assertFalse($checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($regular_user));
        self::assertTrue($checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($user_with_special_rights));
    }
}
