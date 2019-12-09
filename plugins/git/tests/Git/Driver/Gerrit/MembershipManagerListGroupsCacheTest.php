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

require_once __DIR__.'/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MembershipManagerListGroupsCacheTest extends TuleapTestCase
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
        ForgeConfig::store();
        ForgeConfig::set('codendi_log', '/tmp/');
        $this->user                  = stub('PFUser')->getLdapId()->returns('whatever');
        $this->driver                = mock('Git_Driver_Gerrit');
        $this->driver_factory        = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);
        $this->user_finder           = mock('Git_Driver_Gerrit_UserFinder');
        $this->remote_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server         = stub('Git_RemoteServer_GerritServer')->getId()->returns(25);
        $this->gerrit_user           = mock('Git_Driver_Gerrit_User');
        $this->gerrit_user_manager   = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project               = mock('Project');
        $this->u_group               = mock('ProjectUGroup');
        $this->u_group2              = mock('ProjectUGroup');
        $this->u_group3              = mock('ProjectUGroup');
        $this->git_repository        = mock('GitRepository');
        $this->project_manager       = mock('ProjectManager');

        stub($this->u_group)->getProject()->returns($this->project);
        stub($this->u_group2)->getProject()->returns($this->project);
        stub($this->u_group3)->getProject()->returns($this->project);
        stub($this->project_manager)->getChildProjects()->returns(array());

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->project)->getUnixName()->returns($this->project_name);

        stub($this->gerrit_user_manager)->getGerritUser($this->user)->returns($this->gerrit_user);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class),
            $this->driver_factory,
            $this->gerrit_user_manager,
            mock('Git_RemoteServer_GerritServerFactory'),
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

    public function itFetchesGroupsFromDriverOnlyOncePerServer()
    {
        stub($this->driver)->getAllGroups()->returns(array());
        expect($this->driver)->getAllGroups()->once();
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
    }

    public function itCachesSeveralServers()
    {
        $remote_server2 = stub('Git_RemoteServer_GerritServer')->getId()->returns(37);

        stub($this->driver)->getAllGroups()->returns(array());
        expect($this->driver)->getAllGroups()->count(2);
        expect($this->driver)->getAllGroups($this->remote_server)->at(0);
        expect($this->driver)->getAllGroups($remote_server2)->at(1);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
        $this->membership_manager->doesGroupExistOnServer($remote_server2, $this->u_group);
    }
}
