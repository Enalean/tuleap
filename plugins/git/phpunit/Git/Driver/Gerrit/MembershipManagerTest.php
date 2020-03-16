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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    protected $user_ldap_id;
    protected $membership_manager;
    protected $driver;
    protected $user_finder;
    protected $user;
    protected $project_name = 'someProject';
    protected $project;
    protected $u_group_id = 115;
    protected $u_group;
    protected $git_repository_id = 20;
    protected $git_repository_name = 'some/git/project';
    protected $git_repository;
    protected $membership_command_add;
    protected $membership_command_remove;
    protected $gerrit_user;
    protected $gerrit_user_manager;
    protected $remote_server;
    protected $project_manager;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('codendi_log', '/tmp/');
        $this->user                                  = \Mockery::spy(\PFUser::class)->shouldReceive('getLdapId')->andReturns('whatever')->getMock();
        $this->driver                                = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->driver_factory                        = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->driver)->getMock();
        $this->user_finder                           = \Mockery::spy(\Git_Driver_Gerrit_UserFinder::class);
        $this->remote_server_factory                 = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->remote_server                         = \Mockery::spy(\Git_RemoteServer_GerritServer::class)->shouldReceive('getId')->andReturns(25)->getMock();
        $this->gerrit_user                           = \Mockery::spy(\Git_Driver_Gerrit_User::class);
        $this->gerrit_user_manager                   = \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class);
        $this->project                               = \Mockery::spy(\Project::class);
        $this->u_group                               = \Mockery::spy(\ProjectUGroup::class);
        $this->u_group2                              = \Mockery::spy(\ProjectUGroup::class);
        $this->u_group3                              = \Mockery::spy(\ProjectUGroup::class);
        $this->git_repository                        = \Mockery::spy(\GitRepository::class);
        $this->project_manager                       = \Mockery::spy(\ProjectManager::class);

        $this->u_group->shouldReceive('getProject')->andReturns($this->project);
        $this->u_group2->shouldReceive('getProject')->andReturns($this->project);
        $this->u_group3->shouldReceive('getProject')->andReturns($this->project);
        $this->project_manager->shouldReceive('getChildProjects')->andReturns(array());

        $this->remote_server_factory->shouldReceive('getServer')->andReturns($this->remote_server);
        $this->project->shouldReceive('getUnixName')->andReturns($this->project_name);

        $this->gerrit_user_manager->shouldReceive('getGerritUser')->with($this->user)->andReturns($this->gerrit_user);

        $this->git_repository->shouldReceive('getFullName')->andReturns($this->git_repository_name);
        $this->git_repository->shouldReceive('getId')->andReturns($this->git_repository_id);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            Mockery::mock(Git_Driver_Gerrit_MembershipDao::class),
            $this->driver_factory,
            $this->gerrit_user_manager,
            $this->remote_server_factory,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            \Mockery::spy(\UGroupManager::class),
            $this->project_manager
        );
    }

    public function testItAsksTheGerritDriverToAddAUserToThreeGroups(): void
    {
        $this->remote_server_factory->shouldReceive('getServersForUGroup')->andReturns(array($this->remote_server));
        $this->user->shouldReceive('getUgroups')->andReturns(array($this->u_group_id));

        $first_group_expected     = $this->project_name . '/' . 'project_members';
        $second_group_expected    = $this->project_name . '/' . 'project_admins';
        $third_group_expected     = $this->project_name . '/' . 'ldap_group';

        $this->u_group->shouldReceive('getNormalizedName')->andReturns('project_members');
        $this->u_group2->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $this->u_group3->shouldReceive('getNormalizedName')->andReturns('ldap_group');

        $this->driver->shouldReceive('addUserToGroup')->times(3);
        $this->driver->shouldReceive('addUserToGroup')->with($this->remote_server, $this->gerrit_user, $first_group_expected)->ordered();
        $this->driver->shouldReceive('addUserToGroup')->with($this->remote_server, $this->gerrit_user, $second_group_expected)->ordered();
        $this->driver->shouldReceive('addUserToGroup')->with($this->remote_server, $this->gerrit_user, $third_group_expected)->ordered();

        $this->driver->shouldReceive('flushGerritCacheAccounts')->times(3);

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
        $this->membership_manager->addUserToGroup($this->user, $this->u_group2);
        $this->membership_manager->addUserToGroup($this->user, $this->u_group3);
    }

    public function testItAsksTheGerritDriverToRemoveAUserFromThreeGroups(): void
    {
        $this->remote_server_factory->shouldReceive('getServersForUGroup')->andReturns(array($this->remote_server));
        $this->user->shouldReceive('getUgroups')->andReturns(array());

        $first_group_expected     = $this->project_name . '/' . 'project_members';
        $second_group_expected    = $this->project_name . '/' . 'project_admins';
        $third_group_expected     = $this->project_name . '/' . 'ldap_group';

        $this->u_group->shouldReceive('getNormalizedName')->andReturns('project_members');
        $this->u_group2->shouldReceive('getNormalizedName')->andReturns('project_admins');
        $this->u_group3->shouldReceive('getNormalizedName')->andReturns('ldap_group');

        $this->driver->shouldReceive('removeUserFromGroup')->times(3);
        $this->driver->shouldReceive('removeUserFromGroup')->with($this->remote_server, $this->gerrit_user, $first_group_expected)->ordered();
        $this->driver->shouldReceive('removeUserFromGroup')->with($this->remote_server, $this->gerrit_user, $second_group_expected)->ordered();
        $this->driver->shouldReceive('removeUserFromGroup')->with($this->remote_server, $this->gerrit_user, $third_group_expected)->ordered();

        $this->driver->shouldReceive('flushGerritCacheAccounts')->times(3);

        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group);
        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group2);
        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group3);
    }

    public function testItDoesntAddNonLDAPUsersToGerrit(): void
    {
        $this->remote_server_factory->shouldReceive('getServersForUGroup')->andReturns(array($this->remote_server));
        $non_ldap_user = \Mockery::spy(\PFUser::class);
        $non_ldap_user->shouldReceive('getUgroups')->andReturns(array($this->u_group_id));

        $this->driver->shouldReceive('addUserToGroup')->never();

        $this->membership_manager->addUserToGroup($non_ldap_user, $this->u_group);
    }

    public function testItContinuesToAddUserOnOtherServersIfOneOrMoreAreNotReachable(): void
    {
        $this->remote_server2   = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $this->remote_server_factory->shouldReceive('getServersForUGroup')->andReturns(array($this->remote_server, $this->remote_server2));
        $this->user->shouldReceive('getUgroups')->andReturns(array($this->u_group_id));
        $this->u_group->shouldReceive('getNormalizedName')->andReturns('project_members');

        $this->driver->shouldReceive('addUserToGroup')->times(2);
        $this->driver->shouldReceive('addUserToGroup')->with($this->remote_server, \Mockery::any(), \Mockery::any())
            ->ordered()
            ->andThrow(new Git_Driver_Gerrit_Exception('error'));
        $this->driver->shouldReceive('addUserToGroup')->with($this->remote_server2, \Mockery::any(), \Mockery::any())
            ->ordered()
            ->andThrow(new Git_Driver_Gerrit_Exception('error'));

        $this->driver->shouldReceive('flushGerritCacheAccounts')->with($this->remote_server2)->ordered()->once();

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }
}
