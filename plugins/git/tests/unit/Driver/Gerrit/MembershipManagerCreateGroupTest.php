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

use ColinODell\PsrTestLogger\TestLogger;
use Exception;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipDao;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

final class MembershipManagerCreateGroupTest extends TestCase
{
    private TestLogger $logger;
    private Git_Driver_Gerrit_MembershipDao&MockObject $dao;
    private Git_Driver_Gerrit&MockObject $driver;
    private Git_RemoteServer_GerritServer&MockObject $remote_server;
    private ProjectUGroup&MockObject $ugroup;
    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;
    private Project $project;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_server_factory;
    private PFUser $user1;
    private PFUser $user2;

    protected function setUp(): void
    {
        $this->driver        = $this->createMock(Git_Driver_Gerrit::class);
        $this->remote_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_user_manager = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project_manager     = $this->createMock(ProjectManager::class);

        $ugroup_manager = $this->createMock(UGroupManager::class);

        $project_id    = 1236;
        $this->project = ProjectTestBuilder::aProject()->withId($project_id)->withUnixName('w3c')->build();

        $this->ugroup = $this->createMock(ProjectUGroup::class);
        $this->ugroup->method('getId')->willReturn(25698);
        $this->ugroup->method('getNormalizedName')->willReturn('coders');
        $this->ugroup->method('getProject')->willReturn($this->project);
        $this->ugroup->method('getProjectId')->willReturn($project_id);
        $this->ugroup->method('getSourceGroup')->willReturn(null);

        $admin_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withName(ProjectUGroup::PROJECT_ADMIN_NAME)
            ->withProject($this->project)
            ->withoutUsers()
            ->build();

        $ugroup_manager->method('getUGroup')->willReturn($admin_ugroup);

        $this->remote_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->logger                = new TestLogger();
        $this->dao                   = $this->createMock(Git_Driver_Gerrit_MembershipDao::class);
        $this->user1                 = UserTestBuilder::buildWithId(101);
        $this->user2                 = UserTestBuilder::buildWithId(102);

        $gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_driver_factory->method('getDriver')->willReturn($this->driver);
        $this->membership_manager = $this->getMockBuilder(Git_Driver_Gerrit_MembershipManager::class)
            ->setConstructorArgs([
                $this->dao,
                $gerrit_driver_factory,
                $gerrit_user_manager,
                $this->remote_server_factory,
                $this->logger,
                $ugroup_manager,
                $project_manager,
            ])
            ->onlyMethods(['doesGroupExistOnServer', 'addUserToGroupWithoutFlush', 'addUGroupBinding'])
            ->getMock();

        $project_manager->method('getChildProjects')->willReturn([]);
        $this->membership_manager->method('doesGroupExistOnServer')->willReturn(true);

        $this->remote_server->method('getId')->willReturn(666);
        $this->dao->method('addReference');
    }

    public function testItCreateGroupOnAllGerritServersTheProjectUses(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $this->remote_server_factory->expects(self::once())->method('getServersForProject')
            ->with($this->project)
            ->willReturn([$this->remote_server]);
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItCreatesGerritGroupFromUGroup(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->expects(self::once())->method('createGroup')
            ->with($this->remote_server, 'w3c/coders', 'w3c/project_admins')
            ->willReturn('w3c/coders');

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        self::assertEquals('w3c/coders', $gerrit_group_name);
    }

    public function testItAddGroupMembersOnCreation(): void
    {
        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->expects(self::once())->method('createGroup')->with($this->remote_server, 'w3c/coders', 'w3c/project_admins')->willReturn('w3c/coders');

        $mary = UserTestBuilder::buildWithId(112);
        $bob  = UserTestBuilder::buildWithId(125);
        $this->ugroup->method('getMembers')->willReturn([$mary, $bob]);
        $matcher = self::exactly(2);

        $this->membership_manager->expects($matcher)->method('addUserToGroupWithoutFlush')->willReturnCallback(function (...$parameters) use ($matcher, $mary, $bob) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($mary, $parameters[0]);
                self::assertSame($this->ugroup, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($bob, $parameters[0]);
                self::assertSame($this->ugroup, $parameters[1]);
            }
        });

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItStoresTheGroupInTheDb(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $this->remote_server->method('getId')->willReturn(666);
        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->method('createGroup');

        $this->dao->expects(self::once())->method('addReference')->with(1236, 25698, 666);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItDoesntCreateAGroupThatAlreadyExist(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $this->driver->expects(self::once())->method('doesTheGroupExist')
            ->with($this->remote_server, 'w3c/coders')
            ->willReturn(true);

        $this->driver->expects(self::never())->method('createGroup');

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        self::assertEquals('w3c/coders', $gerrit_group_name);
    }

    public function testItAddsMembersToAGroupThatAlreadyExists(): void
    {
        $this->ugroup->method('getMembers')->willReturn([$this->user1, $this->user2]);
        $this->ugroup->method('getId')->willReturn(123);

        $this->driver->method('doesTheGroupExist')->willReturn(true);
        $this->ugroup->method('getSourceGroup')->willReturn(false);
        $matcher = self::exactly(2);

        $this->membership_manager->expects($matcher)->method('addUserToGroupWithoutFlush')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->user1, $parameters[0]);
                self::assertSame($this->ugroup, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->user2, $parameters[0]);
                self::assertSame($this->ugroup, $parameters[1]);
            }
        });

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function testItCreatesGerritGroupOnEachServer(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $remote_server1 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->remote_server_factory->method('getServersForProject')->willReturn([$remote_server1, $remote_server2]);

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $matcher = self::exactly(2);
        $this->driver->expects($matcher)->method('createGroup')->willReturnCallback(function (...$parameters) use ($matcher, $remote_server1, $remote_server2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($remote_server1, $parameters[0]);
                self::assertSame('w3c/coders', $parameters[1]);
                self::assertSame('w3c/project_admins', $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($remote_server2, $parameters[0]);
                self::assertSame('w3c/coders', $parameters[1]);
                self::assertSame('w3c/project_admins', $parameters[2]);
            }
        });

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItStoresTheGroupInTheDbForEachServer(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $remote_server1 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->remote_server_factory->method('getServersForProject')->willReturn([$remote_server1, $remote_server2]);

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->method('createGroup');
        $remote_server1->method('getId')->willReturn(666);
        $remote_server2->method('getId')->willReturn(667);

        $this->dao->expects(self::exactly(2))->method('addReference')
            ->with(1236, 25698, self::callback(static fn(int $id) => $id === 666 || $id === 667));

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItLogsRemoteSSHErrors(): void
    {
        $this->remote_server_factory->method('getServersForProject')->willReturn([$this->remote_server]);

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->method('createGroup')->willThrowException(new Git_Driver_Gerrit_Exception('whatever'));

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        self::assertTrue($this->logger->hasError('whatever'));
    }

    public function testItLogsGerritExceptions(): void
    {
        $this->remote_server_factory->method('getServersForProject')->willReturn([$this->remote_server]);

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->method('createGroup')->willThrowException(new Git_Driver_Gerrit_Exception('whatever'));

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        self::assertTrue($this->logger->hasError('whatever'));
    }

    public function testItLogsAllOtherExceptions(): void
    {
        $this->remote_server_factory->method('getServersForProject')->willReturn([$this->remote_server]);

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->method('createGroup')->willThrowException(new Exception('whatever'));

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        self::assertTrue($this->logger->hasError('Unknown error: whatever'));
    }

    public function testItContinuesToCreateGroupsEvenIfOneFails(): void
    {
        $this->ugroup->method('getMembers')->willReturn([]);
        $remote_server1 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $remote_server2->method('getId')->willReturn(667);
        $this->remote_server_factory->method('getServersForProject')->willReturn([$remote_server1, $remote_server2]);

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $counter = 0;
        $this->driver->expects(self::exactly(2))->method('createGroup')
            ->willReturnCallback(function () use (&$counter) {
                if ($counter++ === 0) {
                    throw new Exception('whatever');
                }
            });
        $this->dao->expects(self::once())->method('addReference')->with(self::anything(), self::anything(), 667);

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function testItDoesntCreateGroupForSpecialNoneUGroup(): void
    {
        $this->driver->expects(self::never())->method('createGroup');

        $ugroup            = new ProjectUGroup(['ugroup_id' => ProjectUGroup::NONE]);
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        self::assertEquals('', $gerrit_group_name);
    }

    public function testItDoesntCreateGroupForSpecialWikiAdminGroup(): void
    {
        $this->driver->expects(self::never())->method('createGroup');

        $ugroup            = new ProjectUGroup(['ugroup_id' => ProjectUGroup::WIKI_ADMIN]);
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        self::assertEquals('', $gerrit_group_name);
    }

    public function testItCreatesGroupForSpecialProjectMembersGroup(): void
    {
        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->expects(self::once())->method('createGroup');

        $ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_MEMBERS)
            ->withName(ProjectUGroup::PROJECT_MEMBERS_NAME)
            ->withProject($this->project)
            ->withoutUsers()
            ->build();

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function testItCreatesGroupForSpecialProjectAdminsGroup(): void
    {
        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->expects(self::once())->method('createGroup');

        $ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)
            ->withName(ProjectUGroup::PROJECT_ADMIN_NAME)
            ->withProject($this->project)
            ->withoutUsers()
            ->build();

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function testItCreatesAnIncludedGroupWhenUGroupIsBinded(): void
    {
        $source_group = ProjectUGroupTestBuilder::buildProjectAdmins();

        $ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(25698)
            ->withName('coders')
            ->withProject($this->project)
            ->withSourceGroup($source_group)
            ->build();

        $this->driver->method('doesTheGroupExist')->willReturn(false);
        $this->driver->expects(self::once())->method('createGroup')->with($this->remote_server, 'w3c/coders', 'w3c/project_admins');
        $this->membership_manager->method('addUGroupBinding')->with($ugroup, $source_group);

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }
}
