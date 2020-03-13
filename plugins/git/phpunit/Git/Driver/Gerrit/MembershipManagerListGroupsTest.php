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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerListGroupsTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    protected $user_ldap_id;
    protected $membership_manager;
    protected $driver;
    protected $user_finder;
    protected $user;
    protected $project_name = 'someProject';
    protected $project;
    protected $u_group;
    protected $git_repository;
    protected $gerrit_user;
    protected $gerrit_user_manager;
    protected $remote_server;
    protected $project_manager;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('codendi_log', vfsStream::setup()->url());
        $this->user                  = \Mockery::spy(\PFUser::class)->shouldReceive('getLdapId')->andReturns('whatever')->getMock();
        $this->driver                = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver_factory        = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();
        $this->user_finder           = \Mockery::spy(\Git_Driver_Gerrit_UserFinder::class);
        $this->remote_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server         = \Mockery::spy(\Git_RemoteServer_GerritServer::class)->shouldReceive('getId')->andReturns(25)->getMock();
        $this->gerrit_user           = \Mockery::spy(\Git_Driver_Gerrit_User::class);
        $this->gerrit_user_manager   = \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class);
        $this->project               = \Mockery::spy(\Project::class);
        $this->u_group               = \Mockery::spy(\ProjectUGroup::class);
        $this->u_group2              = \Mockery::spy(\ProjectUGroup::class);
        $this->u_group3              = \Mockery::spy(\ProjectUGroup::class);
        $this->git_repository        = \Mockery::spy(\GitRepository::class);
        $this->project_manager       = \Mockery::spy(\ProjectManager::class);

        $this->u_group->shouldReceive('getProject')->andReturns($this->project);
        $this->u_group2->shouldReceive('getProject')->andReturns($this->project);
        $this->u_group3->shouldReceive('getProject')->andReturns($this->project);
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());

        $this->remote_server_factory->shouldReceive('getServer')->andReturns($this->remote_server);
        $this->project->shouldReceive('getUnixName')->andReturns($this->project_name);

        $this->gerrit_user_manager->shouldReceive('getGerritUser')->with($this->user)->andReturns($this->gerrit_user);

        $this->membership_manager  = new Git_Driver_Gerrit_MembershipManager(
            \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class),
            $this->driver_factory,
            $this->gerrit_user_manager,
            \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class),
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            \Mockery::spy(\UGroupManager::class),
            $this->project_manager
        );
        $ls_groups_expected_return = array(
            'Administrators' => '31c2cb467c263d73eb24552a7cc98b7131ac2115',
            'Anonymous Users' => 'global:Anonymous-Users',
            'Non-Interactive Users' => '872372f18fd97a7d58bf1f93bc3996d758ffb31b',
            'Project Owners' => 'global:Project-Owners',
            'Registered Users' => 'global:Registered-Users',
            'someProject/project_members' => '53936c4a9782a73e3d5296380feecf6c8cc1076f',
            'someProject/project_admins' => 'ddfaa5d153a40cbf0ae41b73a441dfa97799891b',
            'someProject/group_from_ldap' => 'ec68131cc1adc6b42753c10adb3e3265493f64f9',
        );
        $this->driver->shouldReceive('getAllGroups')->andReturns($ls_groups_expected_return);
    }

    public function testItReturnsTrueWhenGroupExistsOnServer(): void
    {
        $this->u_group->shouldReceive('getNormalizedName')->andReturns('group_from_ldap');

        $this->assertTrue($this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group));
    }

    public function testItReturnsFalseWhenGroupExistsOnServer(): void
    {
        $this->u_group->shouldReceive('getNormalizedName')->andReturns('group_from');

        $this->assertFalse($this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group));
    }

    public function testItReturnsGroupUUIDWhenGroupExists(): void
    {
        $this->assertEquals(
            'ec68131cc1adc6b42753c10adb3e3265493f64f9',
            $this->membership_manager->getGroupUUIDByNameOnServer($this->remote_server, 'someProject/group_from_ldap')
        );
    }

    public function testItRaisesAnExceptionIfGroupDoesntExist(): void
    {
        $this->expectException(Exception::class);
        $this->membership_manager->getGroupUUIDByNameOnServer($this->remote_server, 'someProject/group_from');
    }
}
