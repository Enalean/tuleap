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

require_once __DIR__ .'/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::store();
        ForgeConfig::set('codendi_log', '/tmp/');
        $this->user                                  = stub('PFUser')->getLdapId()->returns('whatever');
        $this->driver                                = mock('Git_Driver_Gerrit');
        $this->driver_factory                        = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);
        $this->user_finder                           = mock('Git_Driver_Gerrit_UserFinder');
        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = stub('Git_RemoteServer_GerritServer')->getId()->returns(25);
        $this->gerrit_user                           = mock('Git_Driver_Gerrit_User');
        $this->gerrit_user_manager                   = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project                               = mock('Project');
        $this->u_group                               = mock('ProjectUGroup');
        $this->u_group2                              = mock('ProjectUGroup');
        $this->u_group3                              = mock('ProjectUGroup');
        $this->git_repository                        = mock('GitRepository');
        $this->project_manager                       = mock('ProjectManager');

        stub($this->u_group)->getProject()->returns($this->project);
        stub($this->u_group2)->getProject()->returns($this->project);
        stub($this->u_group3)->getProject()->returns($this->project);
        stub($this->project_manager)->getChildProjects()->returns(array());

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->project)->getUnixName()->returns($this->project_name);

        stub($this->gerrit_user_manager)->getGerritUser($this->user)->returns($this->gerrit_user);

        stub($this->git_repository)->getFullName()->returns($this->git_repository_name);
        stub($this->git_repository)->getId()->returns($this->git_repository_id);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            safe_mock(Git_Driver_Gerrit_MembershipDao::class),
            $this->driver_factory,
            $this->gerrit_user_manager,
            $this->remote_server_factory,
            mock('Logger'),
            mock('UGroupManager'),
            $this->project_manager
        );
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itAsksTheGerritDriverToAddAUserToThreeGroups()
    {
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
        stub($this->user)->getUgroups()->returns(array($this->u_group_id));

        $first_group_expected     = $this->project_name.'/'.'project_members';
        $second_group_expected    = $this->project_name.'/'.'project_admins';
        $third_group_expected     = $this->project_name.'/'.'ldap_group';

        stub($this->u_group)->getNormalizedName()->returns('project_members');
        stub($this->u_group2)->getNormalizedName()->returns('project_admins');
        stub($this->u_group3)->getNormalizedName()->returns('ldap_group');

        $this->driver->expectCallCount('addUserToGroup', 3);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->gerrit_user, $first_group_expected)->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->gerrit_user, $second_group_expected)->at(1);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->gerrit_user, $third_group_expected)->at(2);

        $this->driver->expectCallCount('flushGerritCacheAccounts', 3);

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
        $this->membership_manager->addUserToGroup($this->user, $this->u_group2);
        $this->membership_manager->addUserToGroup($this->user, $this->u_group3);
    }

    public function itAsksTheGerritDriverToRemoveAUserFromThreeGroups()
    {
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
        stub($this->user)->getUgroups()->returns(array());

        $first_group_expected     = $this->project_name.'/'.'project_members';
        $second_group_expected    = $this->project_name.'/'.'project_admins';
        $third_group_expected     = $this->project_name.'/'.'ldap_group';

        stub($this->u_group)->getNormalizedName()->returns('project_members');
        stub($this->u_group2)->getNormalizedName()->returns('project_admins');
        stub($this->u_group3)->getNormalizedName()->returns('ldap_group');

        $this->driver->expectCallCount('removeUserFromGroup', 3);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->gerrit_user, $first_group_expected)->at(0);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->gerrit_user, $second_group_expected)->at(1);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->gerrit_user, $third_group_expected)->at(2);

        $this->driver->expectCallCount('flushGerritCacheAccounts', 3);

        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group);
        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group2);
        $this->membership_manager->removeUserFromGroup($this->user, $this->u_group3);
    }

    public function itDoesntAddNonLDAPUsersToGerrit()
    {
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
        $non_ldap_user = mock('PFUser');
        stub($non_ldap_user)->getUgroups()->returns(array($this->u_group_id));

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->addUserToGroup($non_ldap_user, $this->u_group);
    }

    public function itContinuesToAddUserOnOtherServersIfOneOrMoreAreNotReachable()
    {
        $this->remote_server2   = mock('Git_RemoteServer_GerritServer');

        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server, $this->remote_server2));
        stub($this->user)->getUgroups()->returns(array($this->u_group_id));
        stub($this->u_group)->getNormalizedName()->returns('project_members');
        stub($this->driver)->addUserToGroup()->throwsAt(0, new Git_Driver_Gerrit_Exception('error'));

        $this->driver->expectCallCount('addUserToGroup', 2);
        expect($this->driver)->addUserToGroup($this->remote_server, '*', '*')->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server2, '*', '*')->at(1);

        expect($this->driver)->flushGerritCacheAccounts()->once();
        expect($this->driver)->flushGerritCacheAccounts($this->remote_server2)->at(0);

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }
}
