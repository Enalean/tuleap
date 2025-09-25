<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ProjectUGroup;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerListGroupsCacheTest extends TestCase
{
    use ForgeConfigSandbox;

    private Git_Driver_Gerrit_MembershipManager $membership_manager;
    private Git_Driver_Gerrit&MockObject $driver;
    private Git_Driver_Gerrit_UserFinder&MockObject $user_finder;
    private string $project_name = 'someProject';
    private ProjectUGroup $u_group;
    private GitRepository $git_repository;
    private Git_RemoteServer_GerritServer&MockObject $remote_server;
    private ProjectUGroup $u_group2;
    private ProjectUGroup $u_group3;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set('codendi_log', '/tmp/');
        $user                  = UserTestBuilder::aUser()->withLdapId('whatever')->build();
        $this->driver          = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory        = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->user_finder     = $this->createMock(Git_Driver_Gerrit_UserFinder::class);
        $remote_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server   = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_user           = $this->createMock(Git_Driver_Gerrit_User::class);
        $gerrit_user_manager   = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project               = ProjectTestBuilder::aProject()->withUnixName($this->project_name)->build();
        $this->u_group         = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withProject($project)->build();
        $this->u_group2        = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withProject($project)->build();
        $this->u_group3        = ProjectUGroupTestBuilder::aCustomUserGroup(103)->withProject($project)->build();
        $this->git_repository  = GitRepositoryTestBuilder::aProjectRepository()->build();
        $project_manager       = $this->createMock(ProjectManager::class);

        $driver_factory->method('getDriver')->willReturn($this->driver);
        $this->remote_server->method('getId')->willReturn(25);

        $project_manager->method('getChildProjects')->willReturn([]);

        $remote_server_factory->method('getServer')->willReturn($this->remote_server);

        $gerrit_user_manager->method('getGerritUser')->with($user)->willReturn($gerrit_user);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
            $driver_factory,
            $gerrit_user_manager,
            $this->createMock(Git_RemoteServer_GerritServerFactory::class),
            new NullLogger(),
            $this->createMock(UGroupManager::class),
            $project_manager
        );
    }

    public function testItFetchesGroupsFromDriverOnlyOncePerServer(): void
    {
        $this->driver->expects($this->once())->method('getAllGroups')->willReturn([]);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
    }

    public function testItCachesSeveralServers(): void
    {
        $remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $remote_server2->method('getId')->willReturn(37);

        $this->driver->expects($this->exactly(2))->method('getAllGroups')->with(
            self::callback(fn(Git_RemoteServer_GerritServer $server) => $server === $this->remote_server || $server === $remote_server2),
        )->willReturn([]);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
        $this->membership_manager->doesGroupExistOnServer($remote_server2, $this->u_group);
    }
}
