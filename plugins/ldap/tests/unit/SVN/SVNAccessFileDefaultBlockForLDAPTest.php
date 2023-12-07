<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\LDAP\SVN;

use Tuleap\ForgeConfigSandbox;
use Tuleap\LDAP\Project\UsesLDAPAuthProvider;
use Tuleap\LDAP\User\LdapLoginFromTuleapUserIdProvider;
use Tuleap\SVNCore\SVNAccessFileDefaultBlockOverride;
use Tuleap\SVNCore\SVNUser;
use Tuleap\SVNCore\SVNUserGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class SVNAccessFileDefaultBlockForLDAPTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @dataProvider membersDataProvider
     */
    public function testUserGroups(SVNAccessFileDefaultBlockOverride $default_block, bool $project_uses_ldap, array $ldap_uids, array $expected): void
    {
        $ldap_logins_provider = new class ($ldap_uids) implements LdapLoginFromTuleapUserIdProvider {
            public function __construct(private array $ldap_uids)
            {
            }

            public function getLdapLoginFromUserIds(array $user_ids): array
            {
                return $this->ldap_uids;
            }
        };

        $uses_ldap_auth = new class ($project_uses_ldap) implements UsesLDAPAuthProvider {
            public function __construct(private bool $project_uses_ldap)
            {
            }

            public function hasSVNLDAPAuth(int $project_id): bool
            {
                return $this->project_uses_ldap;
            }
        };

        $provider = new SVNAccessFileDefaultBlockForLDAP($ldap_logins_provider, $uses_ldap_auth);
        $provider->handle($default_block);

        self::assertEquals(
            $expected,
            $default_block->getSVNUserGroups(),
        );
    }

    public function membersDataProvider(): iterable
    {
        $jmalko    = UserTestBuilder::anActiveUser()->withId(120)->withUserName('jmalko')->build();
        $csteven   = UserTestBuilder::anActiveUser()->withId(121)->withUserName('csteven')->build();
        $disciplus = UserTestBuilder::anActiveUser()->withId(122)->withUserName('disciplus_simplex')->build();

        $project_members = ProjectUGroupTestBuilder::buildProjectMembersWith($jmalko, $csteven, $disciplus);
        $developers      = ProjectUGroupTestBuilder::aCustomUserGroup(230)->withName('Developers')->withUsers($csteven, $disciplus)->build();

        $jm256_svn = new SVNUser($jmalko, 'jm256');
        $cs257_svn = new SVNUser($csteven, 'cs257');
        $ds259_svn = new SVNUser($disciplus, 'ds259');

        $jmalko_svn    = new SVNUser($jmalko, 'jmalko');
        $csteven_svn   = new SVNUser($csteven, 'csteven');
        $disciplus_svn = new SVNUser($disciplus, 'disciplus_simplex');

        $ldap_uids = [
            [ 'user_id' => 120, 'ldap_uid' => 'jm256', 'status' => \PFUser::STATUS_ACTIVE ],
            [ 'user_id' => 121, 'ldap_uid' => 'cs257', 'status' => \PFUser::STATUS_ACTIVE ],
            [ 'user_id' => 122, 'ldap_uid' => 'ds259', 'status' => \PFUser::STATUS_ACTIVE ],
        ];

        return [
            'Nominal case: LDAP logins override default user names' => [
                'default_block' => new SVNAccessFileDefaultBlockOverride(ProjectTestBuilder::aProject()->withAccessPublic()->build(), $project_members, $developers),
                'project_uses_ldap' => true,
                'ldap_uids' => $ldap_uids,
                'expected' => [
                    SVNUserGroup::fromUserGroupAndMembers($project_members, $jm256_svn, $cs257_svn, $ds259_svn),
                    SVNUserGroup::fromUserGroupAndMembers($developers, $cs257_svn, $ds259_svn),
                ],
            ],
            'LDAP uids are converted to lowercase' => [
                'default_block' => new SVNAccessFileDefaultBlockOverride(ProjectTestBuilder::aProject()->withAccessPublic()->build(), $project_members, $developers),
                'project_uses_ldap' => true,
                'ldap_uids' => [
                    [ 'user_id' => 120, 'ldap_uid' => 'JM256', 'status' => \PFUser::STATUS_ACTIVE ],
                    [ 'user_id' => 121, 'ldap_uid' => 'Cs257', 'status' => \PFUser::STATUS_ACTIVE ],
                    [ 'user_id' => 122, 'ldap_uid' => 'dS259', 'status' => \PFUser::STATUS_ACTIVE ],
                ],
                'expected' => [
                    SVNUserGroup::fromUserGroupAndMembers($project_members, $jm256_svn, $cs257_svn, $ds259_svn),
                    SVNUserGroup::fromUserGroupAndMembers($developers, $cs257_svn, $ds259_svn),
                ],
            ],
            'Missing LDAP users are removed' => [
                'default_block' => new SVNAccessFileDefaultBlockOverride(ProjectTestBuilder::aProject()->withAccessPublic()->build(), $project_members, $developers),
                'project_uses_ldap' => true,
                'ldap_uids' => [
                    [ 'user_id' => 120, 'ldap_uid' => 'jm256', 'status' => \PFUser::STATUS_ACTIVE ],
                    [ 'user_id' => 122, 'ldap_uid' => 'ds259', 'status' => \PFUser::STATUS_ACTIVE ],
                ],
                'expected' => [
                    SVNUserGroup::fromUserGroupAndMembers($project_members, $jm256_svn, $ds259_svn),
                    SVNUserGroup::fromUserGroupAndMembers($developers, $ds259_svn),
                ],
            ],
            'No LDAP for project, Tuleap user names are in use' => [
                'default_block' => new SVNAccessFileDefaultBlockOverride(ProjectTestBuilder::aProject()->withAccessPublic()->build(), $project_members, $developers),
                'project_uses_ldap' => false,
                'ldap_uids' => $ldap_uids,
                'expected' => [
                    SVNUserGroup::fromUserGroupAndMembers($project_members, $jmalko_svn, $csteven_svn, $disciplus_svn),
                    SVNUserGroup::fromUserGroupAndMembers($developers, $csteven_svn, $disciplus_svn),
                ],
            ],
        ];
    }

    /**
     * @dataProvider permissionsDataProvider
     */
    public function testPermissions(\Project $project, string $platform_access, bool $project_uses_ldap, bool $is_world_access_forbidden): void
    {
        \ForgeConfig::set(\ForgeAccess::CONFIG, $platform_access);

        $ldap_logins_provider = new class implements LdapLoginFromTuleapUserIdProvider {
            public function getLdapLoginFromUserIds(array $user_ids): array
            {
                return [];
            }
        };

        $uses_ldap_auth = new class ($project_uses_ldap) implements UsesLDAPAuthProvider {
            public function __construct(private bool $project_uses_ldap)
            {
            }

            public function hasSVNLDAPAuth(int $project_id): bool
            {
                return $this->project_uses_ldap;
            }
        };

        $default_block = new SVNAccessFileDefaultBlockOverride($project);
        $provider      = new SVNAccessFileDefaultBlockForLDAP($ldap_logins_provider, $uses_ldap_auth);
        $provider->handle($default_block);

        self::assertSame($is_world_access_forbidden, $default_block->isWorldAccessForbidden());
    }

    public function permissionsDataProvider(): iterable
    {
        $public_project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        return [
            'LDAP plugin override default permissions only when the project is using LDAP for SVN and platform uses restricted (legacy)' => [
                'project'                   => $public_project,
                'platform_access'           => \ForgeAccess::RESTRICTED,
                'project_uses_ldap'         => true,
                'is_world_access_forbidden' => true,
            ],
            'Even when platform uses restricted users, when project does not use LDAP, access is not forbidden (legacy)' => [
                'project'                   => $public_project,
                'platform_access'           => \ForgeAccess::RESTRICTED,
                'project_uses_ldap'         => false,
                'is_world_access_forbidden' => false,
            ],
            'When platform does not use restricted and project uses LDAP, access is not forbidden (legacy)' => [
                'project'                   => $public_project,
                'platform_access'           => \ForgeAccess::REGULAR,
                'project_uses_ldap'         => true,
                'is_world_access_forbidden' => false,
            ],
        ];
    }
}
