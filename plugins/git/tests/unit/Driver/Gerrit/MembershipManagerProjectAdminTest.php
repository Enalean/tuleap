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

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipDao;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_User;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ProjectUGroup;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerProjectAdminTest extends TestCase
{
    private Git_Driver_Gerrit_MembershipManager $membership_manager;
    private Git_Driver_Gerrit&MockObject $driver;
    private PFUser $user;
    private const PROJECT_NAME = 'some_project';
    private const UGROUP_ID    = 115;
    private Git_Driver_Gerrit_User&MockObject $gerrit_user;
    private Git_RemoteServer_GerritServer&MockObject $remote_server;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_server_factory;
    private ProjectUGroup $admin_ugroup;

    protected function setUp(): void
    {
        $this->user                  = $this->createMock(PFUser::class);
        $this->driver                = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory              = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->remote_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server         = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->gerrit_user           = $this->createMock(Git_Driver_Gerrit_User::class);
        $gerrit_user_manager         = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project_manager             = $this->createMock(ProjectManager::class);

        $this->remote_server->method('getId')->willReturn(25);
        $driver_factory->method('getDriver')->willReturn($this->driver);
        $project_manager->method('getChildProjects')->willReturn([]);

        $this->remote_server_factory->method('getServer')->willReturn($this->remote_server);

        $gerrit_user_manager->method('getGerritUser')->with($this->user)->willReturn($this->gerrit_user);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
            $driver_factory,
            $gerrit_user_manager,
            $this->remote_server_factory,
            new NullLogger(),
            $this->createMock(UGroupManager::class),
            $project_manager
        );

        $this->admin_ugroup = $this->createMock(ProjectUGroup::class);
        $this->admin_ugroup->method('getId')->willReturn(ProjectUGroup::PROJECT_ADMIN);
        $this->admin_ugroup->method('getNormalizedName')->willReturn(ProjectUGroup::PROJECT_ADMIN_NAME);
        $this->admin_ugroup->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withUnixName(self::PROJECT_NAME)->build());

        $this->user->method('getLdapId')->willReturn('whatever');
        $this->user->method('getUgroups')->willReturn([self::UGROUP_ID, ProjectUGroup::PROJECT_ADMIN]);
    }

    public function testItProcessesTheListOfGerritServersWhenWeModifyProjectAdminGroup(): void
    {
        $this->remote_server_factory->expects($this->once())->method('getServersForUGroup')
            ->with($this->admin_ugroup)
            ->willReturn([$this->remote_server]);
        $this->driver->method('addUserToGroup');
        $this->driver->method('flushGerritCacheAccounts');

        $this->membership_manager->addUserToGroup($this->user, $this->admin_ugroup);
    }

    public function testItUpdatesGerritProjectAdminsGroupsFromTuleapWhenIAddANewProjectAdmin(): void
    {
        $this->remote_server_factory->method('getServersForUGroup')->willReturn([$this->remote_server]);

        $gerrit_project_project_admins_group_name = self::PROJECT_NAME . '/' . 'project_admins';
        $this->driver->expects($this->once())->method('addUserToGroup')->with($this->remote_server, $this->gerrit_user, $gerrit_project_project_admins_group_name);
        $this->driver->expects($this->once())->method('flushGerritCacheAccounts');

        $this->membership_manager->addUserToGroup($this->user, $this->admin_ugroup);
    }
}
