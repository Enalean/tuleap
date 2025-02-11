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

final class MembershipManagerProjectAdminOwnerOfEverythingTest extends TestCase
{
    private Git_Driver_Gerrit&MockObject $driver;
    private Git_RemoteServer_GerritServer&MockObject $remote_server;
    private ProjectUGroup $admin_ugroup;
    private ProjectUGroup $ugroup;
    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;

    public function setUp(): void
    {
        $this->driver        = $this->createMock(Git_Driver_Gerrit::class);
        $this->remote_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $project_manager     = $this->createMock(ProjectManager::class);

        $ugroup_manager = $this->createMock(UGroupManager::class);

        $gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_driver_factory->method('getDriver')->willReturn($this->driver);
        $membership_dao = $this->createMock(Git_Driver_Gerrit_MembershipDao::class);
        $membership_dao->method('addReference');
        $this->membership_manager = $this->getMockBuilder(Git_Driver_Gerrit_MembershipManager::class)
            ->setConstructorArgs([
                $membership_dao,
                $gerrit_driver_factory,
                $this->createMock(Git_Driver_Gerrit_UserAccountManager::class),
                $this->createMock(Git_RemoteServer_GerritServerFactory::class),
                new NullLogger(),
                $ugroup_manager,
                $project_manager,
            ])
            ->onlyMethods(['doesGroupExistOnServer'])
            ->getMock();

        $project_id = 1236;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->withUnixName('w3c')->build();

        $this->ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(25698)
            ->withName('coders')
            ->withProject($project)
            ->build();

        $this->admin_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withName(ProjectUGroup::PROJECT_ADMIN_NAME)
            ->withProject($project)
            ->withoutUsers()
            ->build();

        $ugroup_manager->method('getUGroup')->willReturn($this->admin_ugroup);

        $project_manager->method('getChildProjects')->willReturn([]);
    }

    public function testItCheckIfProjectAdminsGroupExist(): void
    {
        $this->membership_manager->expects(self::once())->method('doesGroupExistOnServer')->with($this->remote_server, $this->admin_ugroup);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItCreatesTheProjectAdminGroupWhenNoExist(): void
    {
        $this->membership_manager->method('doesGroupExistOnServer')->willReturn(false);
        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $matcher = self::exactly(2);
        $this->driver->expects($matcher)->method('createGroup')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame('w3c/project_admins', $parameters[1]);
                self::assertSame('w3c/project_admins', $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->remote_server, $parameters[0]);
                self::assertSame('w3c/coders', $parameters[1]);
                self::assertSame('w3c/project_admins', $parameters[2]);
            }
        });
        $this->remote_server->method('getId');

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }
}
