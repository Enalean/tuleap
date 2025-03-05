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
use Tuleap\MediawikiStandalone\Permissions\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\User\ForgePermissionsRetriever;
use User_ForgeUserGroupPermission;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserPermissionsBuilderTest extends TestCase
{
    private const READER_UGROUP_ID = 103;
    private const WRITER_UGROUP_ID = 104;
    private const ADMIN_UGROUP_ID  = 105;

    #[\PHPUnit\Framework\Attributes\DataProvider('getAdminTestData')]
    public function testGetPermissionsForAdmin(
        PFUser $user,
        Project $project,
        CheckProjectAccess $check_access,
        bool $is_site_mediawiki_admin,
        bool $is_admin,
    ): void {
        $forge_permissions_retriever = new class ($is_site_mediawiki_admin) implements ForgePermissionsRetriever {
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

        $dao = ISearchByProjectStub::buildWithoutSpecificPermissions();

        $permission_builder = new UserPermissionsBuilder(
            $forge_permissions_retriever,
            $check_access,
            new ProjectPermissionsRetriever($dao),
        );

        $user_permissions = $permission_builder->getPermissions($user, $project);

        self::assertEquals($is_admin, $user_permissions->is_admin);
    }

    public static function getAdminTestData(): iterable
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        return [
            'site administrator has all permissions '                => [
                'user'                    => UserTestBuilder::buildSiteAdministrator(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withValidAccess(),
                'is_site_mediawiki_admin' => false,
                'is_admin'                => true,
            ],
            'site wide mediawiki administrators has all permissions' => [
                'user'                    => UserTestBuilder::anActiveUser()->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withValidAccess(),
                'is_site_mediawiki_admin' => true,
                'is_admin'                => true,
            ],
            'site wide mediawiki administrators has all permissions even on a private project' => [
                'user'                    => UserTestBuilder::anActiveUser()->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withPrivateProjectWithoutAccess(),
                'is_site_mediawiki_admin' => true,
                'is_admin'                => true,
            ],
            'site wide mediawiki administrators has all permissions even if restricted' => [
                'user'                    => UserTestBuilder::anActiveUser()->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withRestrictedUserWithoutAccess(),
                'is_site_mediawiki_admin' => true,
                'is_admin'                => true,
            ],
            'site wide mediawiki administrators has no permission if project is deleted' => [
                'user'                    => UserTestBuilder::anActiveUser()->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withDeletedProject(),
                'is_site_mediawiki_admin' => true,
                'is_admin'                => false,
            ],
            'site wide mediawiki administrators has no permission if project is suspended' => [
                'user'                    => UserTestBuilder::anActiveUser()->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withSuspendedProject(),
                'is_site_mediawiki_admin' => true,
                'is_admin'                => false,
            ],
            'site wide mediawiki administrators has no permission if project is not valid' => [
                'user'                    => UserTestBuilder::anActiveUser()->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withNotValidProject(),
                'is_site_mediawiki_admin' => true,
                'is_admin'                => false,
            ],
            'project administrator has all permissions'              => [
                'user'                    => UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withValidAccess(),
                'is_site_mediawiki_admin' => false,
                'is_admin'                => true,
            ],
            'regular user is not admin'                              => [
                'user'                    => UserTestBuilder::anActiveUser()
                    ->withoutMemberOfProjects()
                    ->withUserGroupMembership(
                        $project,
                        \ProjectUGroup::PROJECT_MEMBERS,
                        false
                    )
                    ->withUserGroupMembership(
                        $project,
                        \ProjectUGroup::PROJECT_ADMIN,
                        false
                    )
                    ->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withValidAccess(),
                'is_site_mediawiki_admin' => false,
                'is_admin'                => false,
            ],
            'project members are not admin'                          => [
                'user'                    => UserTestBuilder::anActiveUser()
                    ->withMemberOf($project)
                    ->withUserGroupMembership(
                        $project,
                        \ProjectUGroup::PROJECT_MEMBERS,
                        true
                    )
                    ->withUserGroupMembership(
                        $project,
                        \ProjectUGroup::PROJECT_ADMIN,
                        false
                    )
                    ->build(),
                'project'                 => $project,
                'check_access'            => CheckProjectAccessStub::withValidAccess(),
                'is_site_mediawiki_admin' => false,
                'is_admin'                => false,
            ],
        ];
    }

    public function getWriterTestData(): iterable
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        return [
            'regular user is not writer'  => [
                'user'      => UserTestBuilder::anActiveUser()
                    ->withoutMemberOfProjects()
                    ->withUserGroupMembership(
                        $project,
                        \ProjectUGroup::PROJECT_MEMBERS,
                        false
                    )->build(),
                'project'   => $project,
                'is_writer' => false,
            ],
            'project members are writers' => [
                'user'      => UserTestBuilder::anActiveUser()
                    ->withMemberOf($project)
                    ->withUserGroupMembership(
                        $project,
                        \ProjectUGroup::PROJECT_MEMBERS,
                        true
                    )->build(),
                'project'   => $project,
                'is_writer' => true,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getReadersTestData')]
    public function testGetPermissionsForReaders(
        PFUser $user,
        Project $project,
        CheckProjectAccess $check_access,
        bool $is_reader,
        bool $is_writer,
        bool $is_admin,
    ): void {
        $forge_permissions_retriever = new class implements ForgePermissionsRetriever {
            public function doesUserHavePermission(PFUser $user, User_ForgeUserGroupPermission $permission): bool
            {
                return false;
            }
        };

        $dao = ISearchByProjectStub::buildWithPermissions(
            [self::READER_UGROUP_ID],
            [self::WRITER_UGROUP_ID],
            [self::ADMIN_UGROUP_ID],
        );

        $permission_builder = new UserPermissionsBuilder(
            $forge_permissions_retriever,
            $check_access,
            new ProjectPermissionsRetriever($dao),
        );

        $user_permissions = $permission_builder->getPermissions($user, $project);

        self::assertEquals($is_reader, $user_permissions->is_reader);
        self::assertEquals($is_writer, $user_permissions->is_writer);
        self::assertEquals($is_admin, $user_permissions->is_admin);
    }

    public static function getReadersTestData(): iterable
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        return [
            'when user is member of allowed ugroup, they can read'                        => [
                'user'         => UserTestBuilder::anActiveUser()
                    ->withUserGroupMembership($project, self::READER_UGROUP_ID, true)
                    ->withUserGroupMembership($project, self::WRITER_UGROUP_ID, false)
                    ->withUserGroupMembership($project, \ProjectUGroup::PROJECT_ADMIN, false)
                    ->withUserGroupMembership($project, self::ADMIN_UGROUP_ID, false)
                    ->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withValidAccess(),
                'is_reader'    => true,
                'is_writer'    => false,
                'is_admin'     => false,
            ],
            'when user is member of allowed ugroup, they can write (and read)'            => [
                'user'         => UserTestBuilder::anActiveUser()
                    ->withUserGroupMembership($project, self::READER_UGROUP_ID, false)
                    ->withUserGroupMembership($project, self::WRITER_UGROUP_ID, true)
                    ->withUserGroupMembership($project, \ProjectUGroup::PROJECT_ADMIN, false)
                    ->withUserGroupMembership($project, self::ADMIN_UGROUP_ID, false)
                    ->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withValidAccess(),
                'is_reader'    => true,
                'is_writer'    => true,
                'is_admin'     => false,
            ],
            'when user is member of allowed ugroup, they can admin (and write, and read)' => [
                'user'         => UserTestBuilder::anActiveUser()
                    ->withUserGroupMembership($project, self::READER_UGROUP_ID, false)
                    ->withUserGroupMembership($project, self::WRITER_UGROUP_ID, false)
                    ->withUserGroupMembership($project, \ProjectUGroup::PROJECT_ADMIN, false)
                    ->withUserGroupMembership($project, self::ADMIN_UGROUP_ID, true)
                    ->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withValidAccess(),
                'is_reader'    => true,
                'is_writer'    => true,
                'is_admin'     => true,
            ],
            'when project is suspended, they cannot read nor write'                       => [
                'user'         => UserTestBuilder::anAnonymousUser()->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withSuspendedProject(),
                'is_reader'    => false,
                'is_writer'    => false,
                'is_admin'     => false,
            ],
            'when project is deleted, they cannot read nor write'                         => [
                'user'         => UserTestBuilder::anAnonymousUser()->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withDeletedProject(),
                'is_reader'    => false,
                'is_writer'    => false,
                'is_admin'     => false,
            ],
            'when user cannot access private project, they cannot read nor write'         => [
                'user'         => UserTestBuilder::anAnonymousUser()->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withPrivateProjectWithoutAccess(),
                'is_reader'    => false,
                'is_writer'    => false,
                'is_admin'     => false,
            ],
            'when restricted user cannot access, they cannot read nor write'              => [
                'user'         => UserTestBuilder::anAnonymousUser()->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withNotValidProject(),
                'is_reader'    => false,
                'is_writer'    => false,
                'is_admin'     => false,
            ],
            'when project is not found, they cannot read nor write'                       => [
                'user'         => UserTestBuilder::anAnonymousUser()->build(),
                'project'      => $project,
                'check_access' => CheckProjectAccessStub::withRestrictedUserWithoutAccess(),
                'is_reader'    => false,
                'is_writer'    => false,
                'is_admin'     => false,
            ],
        ];
    }
}
