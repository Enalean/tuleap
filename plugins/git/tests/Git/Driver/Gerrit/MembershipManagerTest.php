<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../../bootstrap.php';
require_once 'common/include/Config.class.php';

abstract class Git_Driver_Gerrit_MembershipManagerCommonTest extends TuleapTestCase {
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

    public function setUp() {
        Config::store();
        Config::set('codendi_log', '/tmp/');
        $this->user                                  = stub('PFUser')->getLdapId()->returns('whatever');
        $this->driver                                = mock('Git_Driver_Gerrit');
        $this->user_finder                           = mock('Git_Driver_Gerrit_UserFinder');
        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = stub('Git_RemoteServer_GerritServer')->getId()->returns(25);
        $this->gerrit_user                           = mock('Git_Driver_Gerrit_User');
        $this->gerrit_user_manager                   = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project                               = mock('Project');
        $this->u_group                               = mock('UGroup');
        $this->u_group2                              = mock('UGroup');
        $this->u_group3                              = mock('UGroup');
        $this->git_repository                        = mock('GitRepository');
        $this->project_manager                       = mock('ProjectManager');

        stub($this->u_group)->getProject()->returns($this->project);
        stub($this->u_group2)->getProject()->returns($this->project);
        stub($this->u_group3)->getProject()->returns($this->project);
        stub($this->project_manager)->getChildProjects()->returns(array());

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->project)->getUnixName()->returns($this->project_name);

        stub($this->gerrit_user_manager)->getGerritUser($this->user)->returns($this->gerrit_user);
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }
}

class Git_Driver_Gerrit_MembershipManager_NoGerritRepoTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {

    public function setUp() {
        parent::setUp();

        $this->remote_server_factory_without_gerrit = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->remote_server_factory_without_gerrit)->getServersForUGroup()->returns(array());

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            mock('Git_Driver_Gerrit_MembershipDao'),
            $this->driver,
            $this->gerrit_user_manager,
            $this->remote_server_factory_without_gerrit,
            mock('Logger'),
            mock('UGroupManager'),
            $this->project_manager
        );
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
    }

    public function itAsksForAllTheServersOfAProject() {
        stub($this->project)->getId()->returns(456);
        expect($this->remote_server_factory_without_gerrit)->getServersForUGroup($this->u_group)->once();

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }

    public function itDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit() {
        expect($this->driver)->addUserToGroup()->never();
        expect($this->driver)->removeUserFromGroup()->never();

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }

}

abstract class Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {
    public function setUp() {
        parent::setUp();

        stub($this->git_repository)->getFullName()->returns($this->git_repository_name);
        stub($this->git_repository)->getId()->returns($this->git_repository_id);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            mock('Git_Driver_Gerrit_MembershipDao'),
            $this->driver,
            $this->gerrit_user_manager,
            $this->remote_server_factory,
            mock('Logger'),
            mock('UGroupManager'),
            $this->project_manager
        );
    }
}

class Git_Driver_Gerrit_MembershipManagerTest extends Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest {

    public function itAsksTheGerritDriverToAddAUserToThreeGroups() {
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

    public function itAsksTheGerritDriverToRemoveAUserFromThreeGroups() {
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

    public function itDoesntAddNonLDAPUsersToGerrit() {
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
        $non_ldap_user = mock('PFUser');
        stub($non_ldap_user)->getUgroups()->returns(array($this->u_group_id));

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->addUserToGroup($non_ldap_user, $this->u_group);
    }

    public function itContinuesToAddUserOnOtherServersIfOneOrMoreAreNotReachable() {
        $this->remote_server2   = mock('Git_RemoteServer_GerritServer');

        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server, $this->remote_server2));
        stub($this->user)->getUgroups()->returns(array($this->u_group_id));
        stub($this->u_group)->getNormalizedName()->returns('project_members');
        stub($this->driver)->addUserToGroup()->throwsAt(0, new Git_Driver_Gerrit_RemoteSSHCommandFailure(1, 'error', 'error'));

        $this->driver->expectCallCount('addUserToGroup', 2);
        expect($this->driver)->addUserToGroup($this->remote_server, '*', '*')->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server2, '*', '*')->at(1);

        expect($this->driver)->flushGerritCacheAccounts()->once();
        expect($this->driver)->flushGerritCacheAccounts($this->remote_server2)->at(0);

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }
}

class Git_Driver_Gerrit_MembershipManager_ProjectAdminTest extends Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest {

    public function setUp() {
        parent::setUp();

        $this->admin_ugroup = mock('UGroup');
        stub($this->admin_ugroup)->getId()->returns(UGroup::PROJECT_ADMIN);
        stub($this->admin_ugroup)->getProject()->returns($this->project);

        stub($this->user)->getUgroups()->returns(array($this->u_group_id, UGroup::PROJECT_ADMIN));
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
    }

    public function itProcessesTheListOfGerritServersWhenWeModifyProjectAdminGroup() {
        expect($this->remote_server_factory)->getServersForUGroup($this->admin_ugroup)->once();
        $this->membership_manager->addUserToGroup($this->user, $this->admin_ugroup);
    }

    public function itUpdatesGerritProjectAdminsGroupsFromTuleapWhenIAddANewProjectAdmin() {
        stub($this->admin_ugroup)->getNormalizedName()->returns('project_admins');

        $gerrit_project_project_admins_group_name = $this->project_name.'/'.'project_admins';
        expect($this->driver)->addUserToGroup($this->remote_server,  $this->gerrit_user, $gerrit_project_project_admins_group_name)->once();
        expect($this->driver)->flushGerritCacheAccounts()->once();

        $this->membership_manager->addUserToGroup($this->user, $this->admin_ugroup);
    }

//    public function itUpdatesAllGerritGroupsWhenIAddANewProjectAdmin() {
//        stub($this->git_repository_factory)->getAllGerritRepositoriesFromProject()->returns(array(
//            new GitRepositoryWithPermissions(
//                $this->git_repository,
//                array(
//                    Git::PERM_READ          => array(),
//                    Git::PERM_WRITE         => array(UGroup::PROJECT_ADMIN),
//                    Git::PERM_WPLUS         => array(),
//                    Git::SPECIAL_PERM_ADMIN => array(UGroup::PROJECT_ADMIN),
//                )
//            )
//        ));
//
//        stub($this->admin_ugroup)->getName()->returns('project_admins');
//
//        expect($this->driver)->addUserToGroup()->count(1);
//        $gerrit_project_project_admins_group_name = $this->project_name.'/'.'project_admins';
//        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $gerrit_project_project_admins_group_name)->at(1);
//
//        $this->membership_manager->updateUserMembership($this->user, $this->admin_ugroup, $this->project, $this->membership_command_add);
//    }
}

class Git_Driver_Gerrit_MembershipManager_BindedUGroupsTest extends TuleapTestCase {

    /** @var ProjectManager */
    protected $project_manager;

    public function setUp() {
        parent::setUp();

        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = mock('Git_RemoteServer_GerritServer');
        $this->gerrit_user_manager                   = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project_manager                       = mock('ProjectManager');

        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));
        stub($this->project_manager)->getChildProjects()->returns(array());

        $this->driver = mock('Git_Driver_Gerrit');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array('createGroupForServer'),
            array(
                mock('Git_Driver_Gerrit_MembershipDao'),
                $this->driver,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                mock('Logger'),
                mock('UGroupManager'),
                $this->project_manager
            )
        );


        $project = stub('Project')->getUnixName()->returns('mozilla');
        $this->ugroup = new UGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $this->ugroup->setProject($project);
        $this->ugroup->setSourceGroup(null);
        $this->source = new UGroup(array('ugroup_id' => 124, 'name' => 'coders'));
        $this->source->setProject($project);
    }

    public function itAddBindingToAGroup() {
        $gerrit_ugroup_name = 'mozilla/developers';
        $gerrit_source_name = 'mozilla/coders';
        expect($this->driver)->addIncludedGroup($this->remote_server, $gerrit_ugroup_name, $gerrit_source_name)->once();

        expect($this->membership_manager)->createGroupForServer($this->remote_server, $this->source)->once();
        stub($this->membership_manager)->createGroupForServer()->returns('mozilla/coders');

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itEmptyTheMemberListOnBindingAdd() {
        stub($this->membership_manager)->createGroupForServer()->returns('mozilla/coders');

        expect($this->driver)->removeAllGroupMembers($this->remote_server, 'mozilla/developers')->once();

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itReplaceBindingFromAGroupToAnother() {
        $this->ugroup->setSourceGroup($this->source);

        expect($this->driver)->removeAllIncludedGroups($this->remote_server, 'mozilla/developers')->once();

        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itReliesOnCreateGroupForSourceGroupCreation() {
        expect($this->membership_manager)->createGroupForServer($this->remote_server, $this->source)->once();
        $this->membership_manager->addUGroupBinding($this->ugroup, $this->source);
    }

    public function itRemovesBindingWithAGroup() {
        $project = stub('Project')->getUnixName()->returns('mozilla');
        $ugroup = new UGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup(null);

        $gerrit_ugroup_name = 'mozilla/developers';
        expect($this->driver)->removeAllIncludedGroups($this->remote_server, $gerrit_ugroup_name)->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }

    public function itAddsMembersOfPreviousSourceAsHardCodedMembersOnRemove() {
        $user = aUser()->withLdapId('blabla')->build();
        $gerrit_user = mock('Git_Driver_Gerrit_User');
        stub($this->gerrit_user_manager)->getGerritUser($user)->returns($gerrit_user);

        $source_ugroup = mock('UGroup');
        stub($source_ugroup)->getMembers()->returns(array($user));

        $project = stub('Project')->getUnixName()->returns('mozilla');
        $ugroup = new UGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup($source_ugroup);

        expect($this->driver)->addUserToGroup($this->remote_server, $gerrit_user, 'mozilla/developers')->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }
}

class Git_Driver_Gerrit_MembershipManagerGroupCreationCommonTest extends TuleapTestCase {
    /** @var Git_Driver_Gerrit */
    protected $driver;

    /** @var Git_RemoteServer_GerritServer */
    protected $remote_server;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    protected $gerrit_user_manager;

    /** @var UGroup */
    protected $admin_ugroup;

    /** @var UGroup */
    protected $ugroup;

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var ProjectManager */
    protected $project_manager;



    public function setUp() {
        parent::setUp();

        $this->driver              = mock('Git_Driver_Gerrit');
        $this->remote_server       = mock('Git_RemoteServer_GerritServer');
        $this->gerrit_user_manager = mock('Git_Driver_Gerrit_UserAccountManager');
        $this->project_manager     = mock('ProjectManager');

        $this->ugroup_manager = mock('UGroupManager');
        stub($this->project_manager)->getChildProjects()->returns(array());

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'doesGroupExistOnServer'
            ),
            array(
                mock('Git_Driver_Gerrit_MembershipDao'),
                $this->driver,
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

        $this->ugroup = mock('UGroup');
        stub($this->ugroup)->getId()->returns(25698);
        stub($this->ugroup)->getNormalizedName()->returns('coders');
        stub($this->ugroup)->getProject()->returns($this->project);
        stub($this->ugroup)->getProjectId()->returns($project_id);

        $this->admin_ugroup = mock('UGroup');
        stub($this->admin_ugroup)->getId()->returns(UGroup::PROJECT_ADMIN);
        stub($this->admin_ugroup)->getNormalizedName()->returns('project_admins');
        stub($this->admin_ugroup)->getProject()->returns($this->project);
        stub($this->admin_ugroup)->getProjectId()->returns($project_id);
        stub($this->admin_ugroup)->getMembers()->returns(array());

        stub($this->ugroup_manager)->getUGroup()->returns($this->admin_ugroup);
    }
}

class Git_Driver_Gerrit_MembershipManagerProjectAdminsIsDaOwnerOfAllAndEverythingTest extends Git_Driver_Gerrit_MembershipManagerGroupCreationCommonTest {

    public function itCheckIfProjectAdminsGroupExist() {
        stub($this->ugroup)->getMembers()->returns(array());

        expect($this->membership_manager)->doesGroupExistOnServer($this->remote_server, $this->admin_ugroup)->once();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itCreatesTheProjectAdminGroupWhenNoExist() {
        stub($this->ugroup)->getMembers()->returns(array());

        stub($this->membership_manager)->doesGroupExistOnServer()->returns(false);
        expect($this->driver)->createGroup()->count(2);
        expect($this->driver)->createGroup($this->remote_server, 'w3c/project_admins', 'w3c/project_admins')->at(0);
        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', 'w3c/project_admins')->at(1);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }
}


class Git_Driver_Gerrit_MembershipManager_CreateGroupTest extends Git_Driver_Gerrit_MembershipManagerGroupCreationCommonTest {
    private $logger;
    private $dao;

    public function setUp() {
        parent::setUp();

        $this->remote_server_factory  = mock('Git_RemoteServer_GerritServerFactory');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->logger = mock('Logger');
        $this->dao    = mock('Git_Driver_Gerrit_MembershipDao');
        $this->user1 = mock('PFUser');
        $this->user2 = mock('PFUser');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'addUGroupBinding',
                'addUserToGroupWithoutFlush',
                'doesGroupExistOnServer'
            ),
            array(
                $this->dao,
                $this->driver,
                $this->gerrit_user_manager,
                $this->remote_server_factory,
                $this->logger,
                $this->ugroup_manager,
                $this->project_manager
            )
        );

        stub($this->membership_manager)->doesGroupExistOnServer()->returns(true);
    }

    public function itCreateGroupOnAllGerritServersTheProjectUses() {
        stub($this->ugroup)->getMembers()->returns(array());
        expect($this->remote_server_factory)->getServersForProject($this->project)->once();
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itCreatesGerritGroupFromUGroup() {
        stub($this->ugroup)->getMembers()->returns(array());
        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', 'w3c/project_admins')->once();
        stub($this->driver)->createGroup()->returns('w3c/coders');

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
    }

    public function itAddGroupMembersOnCreation() {
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

    public function itStoresTheGroupInTheDb() {
        stub($this->ugroup)->getMembers()->returns(array());
        stub($this->remote_server)->getId()->returns(666);

        expect($this->dao)->addReference(1236, 25698, 666)->once();

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itDoesntCreateAGroupThatAlreadyExist() {
        stub($this->ugroup)->getMembers()->returns(array());
        stub($this->driver)->doesTheGroupExist()->returns(true);
        expect($this->driver)->doesTheGroupExist($this->remote_server, 'w3c/coders')->once();

        expect($this->driver)->createGroup()->never();

        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
    }

    public function itAddsMembersToAGroupThatAlreadyExists() {
        stub($this->ugroup)->getMembers()->returns(array($this->user1, $this->user2));
        stub($this->driver)->doesTheGroupExist()->returns(true);
        stub($this->ugroup)->getSourceGroup()->returns(false);

        expect($this->membership_manager)->addUserToGroupWithoutFlush()->count(2);
        expect($this->membership_manager)->addUserToGroupWithoutFlush($this->user1, $this->ugroup)->at(0);
        expect($this->membership_manager)->addUserToGroupWithoutFlush($this->user2, $this->ugroup)->at(1);

        $this->membership_manager->createGroupForServer($this->remote_server, $this->ugroup);
    }

    public function itCreatesGerritGroupOnEachServer() {
        stub($this->ugroup)->getMembers()->returns(array());
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));


        expect($this->driver)->createGroup()->count(2);
        expect($this->driver)->createGroup($remote_server1, 'w3c/coders', 'w3c/project_admins')->at(0);
        expect($this->driver)->createGroup($remote_server2, 'w3c/coders', 'w3c/project_admins')->at(1);

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itStoresTheGroupInTheDbForEachServer() {
        stub($this->ugroup)->getMembers()->returns(array());
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        stub($remote_server1)->getId()->returns(666);
        stub($remote_server2)->getId()->returns(667);

        expect($this->dao)->addReference()->count(2);
        expect($this->dao)->addReference(1236, 25698, 666)->at(0);
        expect($this->dao)->addReference(1236, 25698, 667)->at(1);

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itLogsRemoteSSHErrors() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure('whatever', 'whatever', 'whatever'));

        expect($this->logger)->error(new PatternExpectation('/^exit_code:/'))->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itLogsGerritExceptions() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Git_Driver_Gerrit_Exception('whatever'));

        expect($this->logger)->error('whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itLogsAllOtherExceptions() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Exception('whatever'));

        expect($this->logger)->error('Unknown error: whatever')->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itContinuesToCreateGroupsEvenIfOneFails() {
        stub($this->ugroup)->getMembers()->returns(array());
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($remote_server2)->getId()->returns(667);
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        expect($this->driver)->createGroup()->count(2);
        stub($this->driver)->createGroup()->throwsAt(0, new Exception('whatever'));
        expect($this->driver)->createGroup($remote_server2, '*', '*')->at(1);
        expect($this->dao)->addReference('*', '*', 667)->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itDoesntCreateGroupForSpecialNoneUGroup() {
        expect($this->driver)->createGroup()->never();

        $ugroup = new UGroup(array('ugroup_id' => UGroup::NONE));
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itDoesntCreateGroupForSpecialWikiAdminGroup() {
        expect($this->driver)->createGroup()->never();

        $ugroup = new UGroup(array('ugroup_id' => UGroup::WIKI_ADMIN));
        $gerrit_group_name = $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itCreatesGroupForSpecialProjectMembersGroup() {
        expect($this->driver)->createGroup()->once();

        $ugroup = mock('UGroup');
        stub($ugroup)->getId()->returns(UGroup::PROJECT_MEMBERS);
        stub($ugroup)->getNormalizedName()->returns('project_members');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);
        stub($ugroup)->getMembers()->returns(array());

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function itCreatesGroupForSpecialProjectAdminsGroup() {
        expect($this->driver)->createGroup()->once();

        $ugroup = mock('UGroup');
        stub($ugroup)->getId()->returns(UGroup::PROJECT_ADMIN);
        stub($ugroup)->getNormalizedName()->returns('project_admin');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);
        stub($ugroup)->getMembers()->returns(array());

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function itCreatesAnIncludedGroupWhenUGroupIsBinded() {
        $source_group = mock('UGroup');

        $ugroup = mock('UGroup');
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

class Git_Driver_Gerrit_MembershipManagerListGroupsTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {

    public function setUp() {
        parent::setUp();

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            mock('Git_Driver_Gerrit_MembershipDao'),
            $this->driver,
            $this->gerrit_user_manager,
            mock('Git_RemoteServer_GerritServerFactory'),
            mock('Logger'),
            mock('UGroupManager'),
            $this->project_manager
        );
        $ls_groups_expected_return = array(
            'Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	Gerrit Site Administrators	INTERNAL	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false',
            'Anonymous Users	global:Anonymous-Users	Any user, signed-in or not	SYSTEM	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false',
            'Non-Interactive Users	872372f18fd97a7d58bf1f93bc3996d758ffb31b	Users who perform batch actions on Gerrit	INTERNAL	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false',
            'Project Owners	global:Project-Owners	Any owner of the project	SYSTEM	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false',
            'Registered Users	global:Registered-Users	Any signed-in user	SYSTEM	Administrators	31c2cb467c263d73eb24552a7cc98b7131ac2115	false',
            'someProject/project_members	53936c4a9782a73e3d5296380feecf6c8cc1076f		INTERNAL	chicken-egg/Demo	53936c4a9782a73e3d5296380feecf6c8cc1076f	false',
            'someProject/project_admins	ddfaa5d153a40cbf0ae41b73a441dfa97799891b		INTERNAL	chicken-egg/LDAP_Admins	ddfaa5d153a40cbf0ae41b73a441dfa97799891b	false',
            'someProject/group_from_ldap	ec68131cc1adc6b42753c10adb3e3265493f64f9		INTERNAL	chicken-egg/LDAP_Others	ec68131cc1adc6b42753c10adb3e3265493f64f9	false',
        );
        stub($this->driver)->listGroupsVerbose()->returns($ls_groups_expected_return);
    }

    public function itReturnsTrueWhenGroupExistsOnServer() {
        stub($this->u_group)->getNormalizedName()->returns('group_from_ldap');

        $this->assertTrue($this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group));
    }

    public function itReturnsFalseWhenGroupExistsOnServer() {
        stub($this->u_group)->getNormalizedName()->returns('group_from');

        $this->assertFalse($this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group));
    }

    public function itReturnsGroupUUIDWhenGroupExists() {
        $this->assertEqual(
            'ec68131cc1adc6b42753c10adb3e3265493f64f9',
            $this->membership_manager->getGroupUUIDByNameOnServer($this->remote_server, 'someProject/group_from_ldap')
        );
    }

    public function itRaisesAnExceptionIfGroupDoesntExist() {
        $this->expectException();
        $this->membership_manager->getGroupUUIDByNameOnServer($this->remote_server, 'someProject/group_from');
    }
}

class Git_Driver_Gerrit_MembershipManagerListGroupsCacheTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {

    public function setUp() {
        parent::setUp();

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            mock('Git_Driver_Gerrit_MembershipDao'),
            $this->driver,
            $this->gerrit_user_manager,
            mock('Git_RemoteServer_GerritServerFactory'),
            mock('Logger'),
            mock('UGroupManager'),
            $this->project_manager
        );
    }
    public function itFetchesGroupsFromDriverOnlyOncePerServer() {
        stub($this->driver)->listGroupsVerbose()->returns(array());
        expect($this->driver)->listGroupsVerbose()->once();
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
    }

    public function itCachesSeveralServers() {
        $remote_server2 = stub('Git_RemoteServer_GerritServer')->getId()->returns(37);

        stub($this->driver)->listGroupsVerbose()->returns(array());
        expect($this->driver)->listGroupsVerbose()->count(2);
        expect($this->driver)->listGroupsVerbose($this->remote_server)->at(0);
        expect($this->driver)->listGroupsVerbose($remote_server2)->at(1);
        $this->membership_manager->doesGroupExistOnServer($this->remote_server, $this->u_group);
        $this->membership_manager->doesGroupExistOnServer($remote_server2, $this->u_group);
    }
}



?>
