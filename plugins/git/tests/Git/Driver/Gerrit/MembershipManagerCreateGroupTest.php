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
class MembershipManagerCreateGroupTest extends TuleapTestCase
{
    private $logger;
    private $dao;

    /** @var Git_Driver_Gerrit */
    protected $driver;

    /** @var Git_RemoteServer_GerritServer */
    protected $remote_server;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    protected $gerrit_user_manager;

    /** @var ProjectUGroup */
    protected $admin_ugroup;

    /** @var ProjectUGroup */
    protected $ugroup;

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var ProjectManager */
    protected $project_manager;

    public function setUp()
    {
        parent::setUp();

        $this->driver              = mock('Git_Driver_Gerrit');
        $this->driver_factory      = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);
        $this->remote_server       = mock('Git_RemoteServer_GerritServer');
        $this->gerrit_user_manager = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project_manager     = mock('ProjectManager');

        $this->ugroup_manager = mock('UGroupManager');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'doesGroupExistOnServer'
            ),
            array(
                \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class),
                $this->driver_factory,
                $this->gerrit_user_manager,
                mock('Git_RemoteServer_GerritServerFactory'),
                mock('Logger'),
                $this->ugroup_manager,
                $this->project_manager
            )
        );

        $project_id    = 1236;
        $this->project = mock('Project');
        stub($this->project)->getID()->returns($project_id);
        stub($this->project)->getUnixName()->returns('w3c');

        $this->ugroup = mock('ProjectUGroup');
        stub($this->ugroup)->getId()->returns(25698);
        stub($this->ugroup)->getNormalizedName()->returns('coders');
        stub($this->ugroup)->getProject()->returns($this->project);
        stub($this->ugroup)->getProjectId()->returns($project_id);

        $this->admin_ugroup = mock('ProjectUGroup');
        stub($this->admin_ugroup)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);
        stub($this->admin_ugroup)->getNormalizedName()->returns('project_admins');
        stub($this->admin_ugroup)->getProject()->returns($this->project);
        stub($this->admin_ugroup)->getProjectId()->returns($project_id);
        stub($this->admin_ugroup)->getMembers()->returns(array());

        stub($this->ugroup_manager)->getUGroup()->returns($this->admin_ugroup);

        $this->remote_server_factory  = mock('Git_RemoteServer_GerritServerFactory');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->logger                 = mock('Logger');
        $this->dao                    = \Mockery::spy(Git_Driver_Gerrit_MembershipDao::class);
        $this->user1                  = mock('PFUser');
        $this->user2                  = mock('PFUser');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'addUGroupBinding',
                'addUserToGroupWithoutFlush',
                'doesGroupExistOnServer'
            ),
            array(
                $this->dao,
                $this->driver_factory,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                $this->logger,
                $this->ugroup_manager,
                $this->project_manager
            )
        );

        stub($this->project_manager)->getChildProjects()->returns(array());
        stub($this->membership_manager)->doesGroupExistOnServer()->returns(true);
    }

    public function itCreateGroupOnAllGerritServersTheProjectUses()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        expect($this->remote_server_factory)->getServersForProject($this->project)->once();
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itCreatesGerritGroupFromUGroup()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', 'w3c/project_admins')->once();
        stub($this->driver)->createGroup()->returns('w3c/coders');

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
    }

    public function itAddGroupMembersOnCreation()
    {
        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', 'w3c/project_admins')->once();
        stub($this->driver)->createGroup()->returns('w3c/coders');

        $mary = aUser()->withId(12)->build();
        $bob  = aUser()->withId(25)->build();
        stub($this->ugroup)->getMembers()->returns(array($mary, $bob));

        stub($this->membership_manager)->addUserToGroupWithoutFlush()->count(2);
        stub($this->membership_manager)->addUserToGroupWithoutFlush($mary, $this->ugroup)->at(0);
        stub($this->membership_manager)->addUserToGroupWithoutFlush($bob, $this->ugroup)->at(1);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itStoresTheGroupInTheDb()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        stub($this->remote_server)->getId()->returns(666);

        $this->dao->shouldReceive('addReference')->with(1236, 25698, 666)->once();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itDoesntCreateAGroupThatAlreadyExist()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        stub($this->driver)->doesTheGroupExist()->returns(true);
        expect($this->driver)->doesTheGroupExist($this->remote_server, 'w3c/coders')->once();

        expect($this->driver)->createGroup()->never();

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
    }

    public function itAddsMembersToAGroupThatAlreadyExists()
    {
        stub($this->ugroup)->getMembers()->returns(array($this->user1, $this->user2));
        stub($this->ugroup)->getId()->returns(123);

        stub($this->driver)->doesTheGroupExist()->returns(true);
        stub($this->ugroup)->getSourceGroup()->returns(false);

        expect($this->membership_manager)->addUserToGroupWithoutFlush()->count(2);
        expect($this->membership_manager)->addUserToGroupWithoutFlush($this->user1, $this->ugroup)->at(0);
        expect($this->membership_manager)->addUserToGroupWithoutFlush($this->user2, $this->ugroup)->at(1);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itCreatesGerritGroupOnEachServer()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        expect($this->driver)->createGroup()->count(2);
        expect($this->driver)->createGroup($remote_server1, 'w3c/coders', 'w3c/project_admins')->at(0);
        expect($this->driver)->createGroup($remote_server2, 'w3c/coders', 'w3c/project_admins')->at(1);

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itStoresTheGroupInTheDbForEachServer()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        stub($remote_server1)->getId()->returns(666);
        stub($remote_server2)->getId()->returns(667);

        $this->dao->shouldReceive('addReference')->with(1236, 25698, 666)->once();
        $this->dao->shouldReceive('addReference')->with(1236, 25698, 667)->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itLogsRemoteSSHErrors()
    {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Git_Driver_Gerrit_Exception('whatever'));

        expect($this->logger)->error(new PatternExpectation('/whatever/'))->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itLogsGerritExceptions()
    {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Git_Driver_Gerrit_Exception('whatever'));

        expect($this->logger)->error('whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itLogsAllOtherExceptions()
    {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Exception('whatever'));

        expect($this->logger)->error('Unknown error: whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itContinuesToCreateGroupsEvenIfOneFails()
    {
        stub($this->ugroup)->getMembers()->returns(array());
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($remote_server2)->getId()->returns(667);
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        expect($this->driver)->createGroup()->count(2);
        stub($this->driver)->createGroup()->throwsAt(0, new Exception('whatever'));
        expect($this->driver)->createGroup($remote_server2, '*', '*')->at(1);
        $this->dao->shouldReceive('addReference')->with(\Mockery::any(), \Mockery::any(), 667)->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itDoesntCreateGroupForSpecialNoneUGroup()
    {
        expect($this->driver)->createGroup()->never();

        $ugroup            = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::NONE));
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itDoesntCreateGroupForSpecialWikiAdminGroup()
    {
        expect($this->driver)->createGroup()->never();

        $ugroup            = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::WIKI_ADMIN));
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itCreatesGroupForSpecialProjectMembersGroup()
    {
        expect($this->driver)->createGroup()->once();

        $ugroup = mock('ProjectUGroup');
        stub($ugroup)->getId()->returns(ProjectUGroup::PROJECT_MEMBERS);
        stub($ugroup)->getNormalizedName()->returns('project_members');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);
        stub($ugroup)->getMembers()->returns(array());

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function itCreatesGroupForSpecialProjectAdminsGroup()
    {
        expect($this->driver)->createGroup()->once();

        $ugroup = mock('ProjectUGroup');
        stub($ugroup)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);
        stub($ugroup)->getNormalizedName()->returns('project_admin');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);
        stub($ugroup)->getMembers()->returns(array());

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function itCreatesAnIncludedGroupWhenUGroupIsBinded()
    {
        $source_group = mock('ProjectUGroup');

        $ugroup = mock('ProjectUGroup');
        stub($ugroup)->getId()->returns(25698);
        stub($ugroup)->getNormalizedName()->returns('coders');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);
        stub($ugroup)->getSourceGroup()->returns($source_group);

        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', 'w3c/project_admins')->once();
        expect($this->membership_manager)->addUGroupBinding($ugroup, $source_group);

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }
}
