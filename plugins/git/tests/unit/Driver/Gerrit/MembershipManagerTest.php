<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Git\Driver\Gerrit;

use ForgeConfig;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipDao;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_User;
use Git_Driver_Gerrit_UserAccountManager;
use Git_Driver_Gerrit_UserFinder;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ProjectUGroup;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerTest extends TestCase
{
    use ForgeConfigSandbox;

    private int $user_ldap_id;
    private Git_Driver_Gerrit_MembershipManager $membership_manager;
    private Git_Driver_Gerrit&MockObject $driver;
    private Git_Driver_Gerrit_UserFinder&MockObject $user_finder;
    private PFUser&MockObject $user;
    private string $project_name = 'some_project';
    private int $u_group_id      = 115;
    private ProjectUGroup $u_group;
    private int $git_repository_id      = 20;
    private string $git_repository_name = 'some/git/project';
    private GitRepository $git_repository;
    private Git_Driver_Gerrit_User&MockObject $gerrit_user;
    private Git_Driver_Gerrit_UserAccountManager&MockObject $gerrit_user_manager;
    private Git_RemoteServer_GerritServer&MockObject $remote_server;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_server_factory;
    private ProjectUGroup $u_group2;
    private ProjectUGroup $u_group3;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set('codendi_log', '/tmp/');
        $this->user                  = $this->createMock(PFUser::class);
        $this->driver                = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory              = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->user_finder           = $this->createMock(Git_Driver_Gerrit_UserFinder::class);
        $this->remote_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server         = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->gerrit_user           = $this->createMock(Git_Driver_Gerrit_User::class);
        $this->gerrit_user_manager   = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project                     = ProjectTestBuilder::aProject()->withUnixName($this->project_name)->build();
        $this->u_group               = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withProject($project)->withName('project_members')->build();
        $this->u_group2              = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withProject($project)->withName('project_admins')->build();
        $this->u_group3              = ProjectUGroupTestBuilder::aCustomUserGroup(103)->withProject($project)->withName('ldap_group')->build();
        $this->git_repository        = GitRepositoryTestBuilder::aProjectRepository()->withName($this->git_repository_name)->withId($this->git_repository_id)->build();
        $project_manager             = $this->createMock(ProjectManager::class);

        $this->user->method('getLdapId')->willReturn('whatever');
        $driver_factory->method('getDriver')->willReturn($this->driver);
        $this->remote_server->method('getId')->willReturn(25);
        $project_manager->method('getChildProjects')->willReturn([]);

        $this->remote_server_factory->method('getServer')->willReturn($this->remote_server);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
            $driver_factory,
            $this->gerrit_user_manager,
            $this->remote_server_factory,
            new NullLogger(),
            $this->createMock(UGroupManager::class),
            $project_manager
        );
    }

    public function testItAsksTheGerritDriverToAddAUserToThreeGroups(): void
    {
        $this->remote_server_factory->method('getServersForUGroup')->willReturn([$this->remote_server]);
        $this->user->method('getUgroups')->willReturn([$this->u_group_id]);
        $this->gerrit_user_manager->method('getGerritUser')->with($this->user)->willReturn($this->gerrit_user);

        $first_group_expected  = $this->project_name . '/' . 'project_members';
        $second_group_expected = $this->project_name . '/' . 'project_admins';
        $third_group_expected  = $this->project_name . '/' . 'ldap_group';
        $matcher               = self::exactly(3);

        $this->driver->expects($matcher)->method('addUserToGroup')->willReturnCallback(function (...$parameters) use ($matcher, $first_group_expected, $second_group_expected, $third_group_expected) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($first_group_expected, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($second_group_expected, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($third_group_expected, $parameters[2]);
            }
        });

        $this->driver->expects($this->exactly(3))->method('flushGerritCacheAccounts');

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
        $this->membership_manager->addUserToGroup($this->user, $this->u_group2);
        $this->membership_manager->addUserToGroup($this->user, $this->u_group3);
    }

    public function testItAsksTheGerritDriverToRemoveAUserFromThreeGroups(): void
    {
        $this->remote_server_factory->method('getServersForUGroup')->willReturn([$this->remote_server]);
        $this->user->method('getUgroups')->willReturn([]);
        $this->gerrit_user_manager->method('getGerritUser')->with($this->user)->willReturn($this->gerrit_user);

        $first_group_expected  = $this->project_name . '/' . 'project_members';
        $second_group_expected = $this->project_name . '/' . 'project_admins';
        $third_group_expected  = $this->project_name . '/' . 'ldap_group';
        $matcher               = self::exactly(3);

        $this->driver->expects($matcher)->method('removeUserFromGroup')->willReturnCallback(function (...$parameters) use ($matcher, $first_group_expected, $second_group_expected, $third_group_expected) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($first_group_expected, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($second_group_expected, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($third_group_expected, $parameters[2]);
            }
        });

        $this->driver->expects($this->exactly(3))->method('flushGerritCacheAccounts');

        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group);
        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group2);
        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group3);
    }

    public function testItDoesntAddNonLDAPUsersToGerrit(): void
    {
        $this->remote_server_factory->method('getServersForUGroup')->willReturn([$this->remote_server]);
        $non_ldap_user = $this->createMock(PFUser::class);
        $non_ldap_user->method('getUgroups')->willReturn([$this->u_group_id]);

        $this->gerrit_user_manager->method('getGerritUser')->willReturn(null);
        $this->driver->expects($this->never())->method('addUserToGroup');

        $this->membership_manager->addUserToGroup($non_ldap_user, $this->u_group);
    }

    public function testItContinuesToAddUserOnOtherServersIfOneOrMoreAreNotReachable(): void
    {
        $remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);

        $this->remote_server_factory->method('getServersForUGroup')->willReturn([$this->remote_server, $remote_server2]);
        $this->user->method('getUgroups')->willReturn([$this->u_group_id]);
        $this->gerrit_user_manager->method('getGerritUser')->with($this->user)->willReturn($this->gerrit_user);

        $matcher = $this->exactly(2);
        $this->driver->expects($matcher)->method('addUserToGroup')
            ->willReturnCallback(function (Git_RemoteServer_GerritServer $server) use ($matcher, $remote_server2) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->remote_server, $server);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($remote_server2, $server);
                }
            });

        $this->driver->expects($this->atLeastOnce())->method('flushGerritCacheAccounts')->with($remote_server2);

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }
}
