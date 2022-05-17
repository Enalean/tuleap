<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Permissions;

use PFUser;
use Project;
use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\ForgePermissionsRetriever;
use User_ForgeUserGroupPermission;

final class UserPermissionsBuilderTest extends TestCase
{
    /**
     * @dataProvider getAdminTestData
     */
    public function testGetPermissionsForAdmin(PFUser $user, Project $project, bool $is_site_mediawiki_admin, bool $is_admin): void
    {
        $forge_permissions_retriever = new class ($is_site_mediawiki_admin) implements ForgePermissionsRetriever
        {
            public function __construct(private bool $has_permissions)
            {
            }

            public function doesUserHavePermission(PFUser $user, User_ForgeUserGroupPermission $permission): bool
            {
                if ($permission instanceof MediawikiAdminAllProjects) {
                    return $this->has_permissions;
                }
                return false;
            }
        };

        $permission_builder = new UserPermissionsBuilder($forge_permissions_retriever);

        $user_permissions = $permission_builder->getPermissions($user, $project);

        self::assertEquals($is_admin, $user_permissions->is_admin);
    }

    public function getAdminTestData(): iterable
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        return [
            'site administrator has all permissions ' => [
                'user'        => UserTestBuilder::buildSiteAdministrator(),
                'project' => $project,
                'is_site_mediawiki_admin' => false,
                'is_admin' => true,
            ],
            'site wide mediawiki administrators has all permissions' => [
                'user' => UserTestBuilder::anActiveUser()->build(),
                'project' => $project,
                'is_site_mediawiki_admin' => true,
                'is_admin' => true,
            ],
            'project administrator has all permissions' => [
                'user' => UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build(),
                'project' => $project,
                'is_site_mediawiki_admin' => false,
                'is_admin' => true,
            ],
            'regular user is not admin' => [
                'user' => UserTestBuilder::anActiveUser()->withoutMemberOfProjects()->build(),
                'project' => $project,
                'is_site_mediawiki_admin' => false,
                'is_admin' => false,
            ],
            'project members are not admin' => [
                'user' => UserTestBuilder::anActiveUser()->withMemberOf($project)->build(),
                'project' => $project,
                'is_site_mediawiki_admin' => false,
                'is_admin' => false,
            ],
        ];
    }

    /**
     * @dataProvider getWriterTestData
     */
    public function testGetPermissionsForWriters(PFUser $user, Project $project, bool $is_writer): void
    {
        $forge_permissions_retriever = new class implements ForgePermissionsRetriever
        {
            public function doesUserHavePermission(PFUser $user, User_ForgeUserGroupPermission $permission): bool
            {
                return false;
            }
        };

        $permission_builder = new UserPermissionsBuilder($forge_permissions_retriever);

        $user_permissions = $permission_builder->getPermissions($user, $project);

        self::assertEquals($is_writer, $user_permissions->is_writer);
    }

    public function getWriterTestData(): iterable
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        return [
            'regular user is not writer' => [
                'user' => UserTestBuilder::anActiveUser()->withoutMemberOfProjects()->build(),
                'project' => $project,
                'is_writer' => false,
            ],
            'project members are writers' => [
                'user' => UserTestBuilder::anActiveUser()->withMemberOf($project)->build(),
                'project' => $project,
                'is_writer' => true,
            ],
        ];
    }
}
