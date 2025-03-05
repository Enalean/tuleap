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

use Exception;
use ForgeConfig;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_MembershipDao;
use Git_Driver_Gerrit_MembershipManager;
use Git_Driver_Gerrit_User;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ProjectUGroup;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MembershipManagerListGroupsTest extends TestCase
{
    use ForgeConfigSandbox;

    private Git_Driver_Gerrit_MembershipManager $membership_manager;
    private ProjectUGroup $u_group;
    private ProjectUGroup $u_group2;
    private Git_RemoteServer_GerritServer&MockObject $remote_server;

    protected function setUp(): void
    {
        ForgeConfig::set('codendi_log', vfsStream::setup()->url());
        $user                  = UserTestBuilder::aUser()->withLdapId('whatever')->build();
        $driver                = $this->createMock(Git_Driver_Gerrit::class);
        $remote_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server   = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_user           = $this->createMock(Git_Driver_Gerrit_User::class);
        $gerrit_user_manager   = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);
        $project               = ProjectTestBuilder::aProject()->withUnixName('some_project')->build();
        $this->u_group         = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName('group_from_ldap')->withProject($project)->build();
        $this->u_group2        = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('group_from')->withProject($project)->build();
        $project_manager       = $this->createMock(ProjectManager::class);

        $this->remote_server->method('getId')->willReturn(25);
        $project_manager->method('getChildProjects')->willReturn([]);

        $remote_server_factory->method('getServer')->willReturn($this->remote_server);

        $gerrit_user_manager->method('getGerritUser')->with($user)->willReturn($gerrit_user);

        $gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $gerrit_driver_factory->method('getDriver')->willReturn($driver);
        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->createMock(Git_Driver_Gerrit_MembershipDao::class),
            $gerrit_driver_factory,
            $gerrit_user_manager,
            $this->createMock(Git_RemoteServer_GerritServerFactory::class),
            new NullLogger(),
            $this->createMock(UGroupManager::class),
            $project_manager
        );
        $driver->method('getAllGroups')->willReturn([
            'Administrators'               => '31c2cb467c263d73eb24552a7cc98b7131ac2115',
            'Anonymous Users'              => 'global:Anonymous-Users',
            'Non-Interactive Users'        => '872372f18fd97a7d58bf1f93bc3996d758ffb31b',
            'Project Owners'               => 'global:Project-Owners',
            'Registered Users'             => 'global:Registered-Users',
            'some_project/project_members' => '53936c4a9782a73e3d5296380feecf6c8cc1076f',
            'some_project/project_admins'  => 'ddfaa5d153a40cbf0ae41b73a441dfa97799891b',
            'some_project/group_from_ldap' => 'ec68131cc1adc6b42753c10adb3e3265493f64f9',
        ]);
    }

    public function testItReturnsTrueWhenGroupExistsOnServer(): void
    {
        self::assertTrue($this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group));
    }

    public function testItReturnsFalseWhenGroupExistsOnServer(): void
    {
        self::assertFalse($this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group2));
    }

    public function testItReturnsGroupUUIDWhenGroupExists(): void
    {
        self::assertEquals(
            'ec68131cc1adc6b42753c10adb3e3265493f64f9',
            $this->membership_manager->getGroupUUIDByNameOnServer($this->remote_server, 'some_project/group_from_ldap')
        );
    }

    public function testItRaisesAnExceptionIfGroupDoesntExist(): void
    {
        $this->remote_server->method('getBaseUrl');
        $this->expectException(Exception::class);
        $this->membership_manager->getGroupUUIDByNameOnServer($this->remote_server, 'some_project/group_from');
    }
}
