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
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerBindedUGroupsTest extends TestCase
{
    private Git_RemoteServer_GerritServer&MockObject $remote_server;
    private Git_Driver_Gerrit_UserAccountManager&MockObject $gerrit_user_manager;
    private Git_Driver_Gerrit&MockObject $driver;
    private Git_Driver_Gerrit_MembershipManager&MockObject $membership_manager;
    private ProjectUGroup $ugroup;
    private ProjectUGroup $source;

    #[\Override]
    protected function setUp(): void
    {
        $remote_server_factory     = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server       = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->gerrit_user_manager = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project_manager           = $this->createMock(ProjectManager::class);

        $remote_server_factory->method('getServersForUGroup')->willReturn([$this->remote_server]);
        $project_manager->method('getChildProjects')->willReturn([]);

        $this->driver   = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $driver_factory->method('getDriver')->willReturn($this->driver);

        $this->membership_manager = $this->getMockBuilder(Git_Driver_Gerrit_MembershipManager::class)
            ->setConstructorArgs([
                $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
                $driver_factory,
                $this->gerrit_user_manager,
                $remote_server_factory,
                new NullLogger(),
                $this->createMock(UGroupManager::class),
                $project_manager,
            ])
            ->onlyMethods(['createGroupForServer'])
            ->getMock();

        $project      = ProjectTestBuilder::aProject()->withUnixName('mozilla')->build();
        $this->ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(112)->withName('developers')->withProject($project)->build();
        $this->source = ProjectUGroupTestBuilder::aCustomUserGroup(124)->withName('coders')->withProject($project)->build();
    }

    public function testItAddBindingToAGroup(): void
    {
        $gerrit_ugroup_name = 'mozilla/developers';
        $gerrit_source_name = 'mozilla/coders';
        $this->driver->expects($this->once())->method('addIncludedGroup')->with($this->remote_server, $gerrit_ugroup_name, $gerrit_source_name);

        $this->membership_manager->expects($this->once())->method('createGroupForServer')
            ->with($this->remote_server, $this->source)
            ->willReturn('mozilla/coders');
        $this->driver->method('removeAllGroupMembers');

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItEmptyTheMemberListOnBindingAdd(): void
    {
        $this->membership_manager->method('createGroupForServer')->willReturn('mozilla/coders');

        $this->driver->expects($this->once())->method('removeAllGroupMembers')->with($this->remote_server, 'mozilla/developers');
        $this->driver->method('addIncludedGroup');

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItReplaceBindingFromAGroupToAnother(): void
    {
        $this->membership_manager->method('createGroupForServer');

        $this->ugroup->setSourceGroup($this->source);

        $this->driver->expects($this->once())->method('removeAllIncludedGroups')->with($this->remote_server, 'mozilla/developers');
        $this->driver->method('removeAllGroupMembers');
        $this->driver->method('addIncludedGroup');

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItReliesOnCreateGroupForSourceGroupCreation(): void
    {
        $this->membership_manager->expects($this->once())->method('createGroupForServer')->with($this->remote_server, $this->source);
        $this->driver->method('removeAllGroupMembers');
        $this->driver->method('addIncludedGroup');
        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function testItRemovesBindingWithAGroup(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('mozilla')->build();
        $ugroup  = ProjectUGroupTestBuilder::aCustomUserGroup(112)->withProject($project)->withName('developers')->build();

        $gerrit_ugroup_name = 'mozilla/developers';
        $this->driver->expects($this->once())->method('removeAllIncludedGroups')->with($this->remote_server, $gerrit_ugroup_name);

        $this->membership_manager->removeUGroupBinding($ugroup);
    }

    public function testItAddsMembersOfPreviousSourceAsHardCodedMembersOnRemove(): void
    {
        $user        = new PFUser([
            'language_id' => 'en',
            'ldap_id'     => 'blabla',
        ]);
        $gerrit_user = $this->createMock(Git_Driver_Gerrit_User::class);
        $this->gerrit_user_manager->method('getGerritUser')->with($user)->willReturn($gerrit_user);

        $source_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(452)->withUsers($user)->build();

        $project = ProjectTestBuilder::aProject()->withUnixName('mozilla')->build();
        $ugroup  = ProjectUGroupTestBuilder::aCustomUserGroup(112)->withProject($project)->withName('developers')->withSourceGroup($source_ugroup)->build();

        $this->driver->expects($this->once())->method('addUserToGroup')->with($this->remote_server, $gerrit_user, 'mozilla/developers');
        $this->driver->method('removeAllIncludedGroups');
        $this->driver->method('flushGerritCacheAccounts');

        $this->membership_manager->removeUGroupBinding($ugroup);
    }
}
