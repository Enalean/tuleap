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
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerNoGerritTest extends TestCase
{
    private Git_Driver_Gerrit_MembershipManager $membership_manager;
    private Git_Driver_Gerrit&MockObject $driver;
    private PFUser $user;
    private ProjectUGroup $u_group;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_server_factory_without_gerrit;

    protected function setUp(): void
    {
        $this->user          = UserTestBuilder::aUser()->withLdapId('whatever')->build();
        $this->driver        = $this->createMock(Git_Driver_Gerrit::class);
        $remote_server       = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_user         = $this->createMock(Git_Driver_Gerrit_User::class);
        $gerrit_user_manager = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project             = ProjectTestBuilder::aProject()->withId(456)->withUnixName('some_project')->build();
        $this->u_group       = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withProject($project)->build();
        $project_manager     = $this->createMock(ProjectManager::class);

        $remote_server->method('getId')->willReturn(25);
        $project_manager->method('getChildProjects')->willReturn([]);

        $gerrit_user_manager->method('getGerritUser')->with($this->user)->willReturn($gerrit_user);

        $this->remote_server_factory_without_gerrit = $this->createMock(Git_RemoteServer_GerritServerFactory::class);

        $gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_driver_factory->method('getDriver')->willReturn($this->driver);
        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
            $gerrit_driver_factory,
            $gerrit_user_manager,
            $this->remote_server_factory_without_gerrit,
            new NullLogger(),
            $this->createMock(UGroupManager::class),
            $project_manager
        );
    }

    public function testItAsksForAllTheServersOfAProject(): void
    {
        $this->remote_server_factory_without_gerrit->expects($this->once())->method('getServersForUGroup')
            ->with($this->u_group)->willReturn([]);

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }

    public function testItDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit(): void
    {
        $this->remote_server_factory_without_gerrit->method('getServersForUGroup')->willReturn([]);

        $this->driver->expects($this->never())->method('addUserToGroup');
        $this->driver->expects($this->never())->method('removeUserFromGroup');

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }
}
