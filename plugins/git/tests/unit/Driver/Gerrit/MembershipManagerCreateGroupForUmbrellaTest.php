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
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ProjectUGroup;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

/**
 * Fix for request #5031 - Fatal error when adding a group in an umbrella parent project
 * @see https://tuleap.net/plugins/tracker/?aid=5031
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerCreateGroupForUmbrellaTest extends TestCase
{
    private ProjectUGroup $ugroup;
    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_server_factory;

    protected function setUp(): void
    {
        $driver              = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory      = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_user_manager = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project_manager     = $this->createMock(ProjectManager::class);
        $ugroup_manager      = $this->createMock(UGroupManager::class);

        $driver_factory->method('getDriver')->willReturn($driver);

        $project = ProjectTestBuilder::aProject()->withId(1236)->withUnixName('w3c')->build();

        $this->ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(25698)
            ->withName('coders')
            ->withProject($project)
            ->withoutUsers()
            ->build();
        $admin_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withName(ProjectUGroup::PROJECT_ADMIN_NAME)
            ->withProject($project)
            ->withoutUsers()
            ->build();

        $ugroup_manager->method('getUGroup')->willReturn($admin_ugroup);

        $this->remote_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);

        $this->membership_manager = $this->getMockBuilder(Git_Driver_Gerrit_MembershipManager::class)
            ->setConstructorArgs([
                $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
                $driver_factory,
                $gerrit_user_manager,
                $this->remote_server_factory,
                new NullLogger(),
                $ugroup_manager,
                $project_manager,
            ])
            ->onlyMethods(['doesGroupExistOnServer'])
            ->getMock();

        $this->membership_manager->method('doesGroupExistOnServer')->willReturn(true);

        $child_project = ProjectTestBuilder::aProject()->withId(112)->withAccessPrivate()->build();
        $project_manager->method('getChildProjects')->willReturn([$child_project]);
    }

    public function testItCreateGroupOnAllGerritServersTheProjectAndItsChildrenUse(): void
    {
        $this->remote_server_factory->expects($this->atLeastOnce())->method('getServersForProject')->willReturn(
            [$this->buildGerritServer(3)],
            [$this->buildGerritServer(5)],
        );
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    private function buildGerritServer(int $id): Git_RemoteServer_GerritServer
    {
        $server = $this->createPartialMock(Git_RemoteServer_GerritServer::class, ['getId']);
        $server->method('getId')->willReturn($id);

        return $server;
    }
}
