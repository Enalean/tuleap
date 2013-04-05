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

require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once dirname(__FILE__).'/../../../builders/aGitRepository.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit/MembershipManager.class.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit/MembershipCommand/AddUser.class.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit/MembershipCommand/RemoveUser.class.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit.class.php';
require_once 'common/include/Config.class.php';

abstract class Git_Driver_Gerrit_MembershipManagerCommonTest extends TuleapTestCase {
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

    public function setUp() {
        $this->user                                  = stub('PFUser')->getLdapId()->returns('whatever');
        $this->driver                                = mock('Git_Driver_Gerrit');
        $this->user_finder                           = mock('Git_Driver_Gerrit_UserFinder');
        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = mock('Git_RemoteServer_GerritServer');
        $this->project                               = mock('Project');
        $this->u_group                               = mock('UGroup');
        $this->u_group2                              = mock('UGroup');
        $this->u_group3                              = mock('UGroup');
        $this->git_repository                        = mock('GitRepository');

        stub($this->u_group)->getProject()->returns($this->project);
        stub($this->u_group2)->getProject()->returns($this->project);
        stub($this->u_group3)->getProject()->returns($this->project);

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->project)->getUnixName()->returns($this->project_name);
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
            $this->remote_server_factory_without_gerrit,
            mock('Logger')
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
            $this->remote_server_factory,
            mock('Logger')
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
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

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
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

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

        $this->membership_manager->addUserToGroup($this->user, $this->u_group);
    }
}

//class Git_Driver_Gerrit_MembershipManager_SeveralUGroupsTest extends Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest {
//    private $u_group_id_120 = 120;
//
//    public function setUp() {
//        parent::setUp();
//
//        $git_permissions = array(
//            $this->git_repository,
//        );
//
//        stub($this->git_repository_factory)->getAllRepositories()->returns($git_permissions);
//    }
//
////    public function itDoesntRemoveUserIfTheyBelongToAtLeastOneGroupThatHaveAccess() {
////        // User was removed from ugroup 115 but is still member of ugroup 120
////        stub($this->user)->getUgroups()->returns(array($this->u_group_id_120));
////        expect($this->driver)->removeUserFromGroup()->never();
////        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_remove);
////    }
//}

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

        expect($this->driver)->addUserToGroup()->count(1);
        $gerrit_project_project_admins_group_name = $this->project_name.'/'.'project_admins';
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $gerrit_project_project_admins_group_name)->once();

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
    public function setUp() {
        parent::setUp();

        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = mock('Git_RemoteServer_GerritServer');
        stub($this->remote_server_factory)->getServersForUGroup()->returns(array($this->remote_server));

        $this->driver = mock('Git_Driver_Gerrit');
        
        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array('createGroupForServer'),
            array(
                mock('Git_Driver_Gerrit_MembershipDao'),
                $this->driver,
                $this->remote_server_factory,
                mock('Logger')
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

        $source_ugroup = mock('UGroup');
        stub($source_ugroup)->getMembers()->returns(array($user));

        $project = stub('Project')->getUnixName()->returns('mozilla');
        $ugroup = new UGroup(array('ugroup_id' => 112, 'name' => 'developers'));
        $ugroup->setProject($project);
        $ugroup->setSourceGroup($source_ugroup);

        expect($this->driver)->addUserToGroup($this->remote_server, $user, 'mozilla/developers')->once();

        $this->membership_manager->removeUGroupBinding($ugroup);
    }
}

class Git_Driver_Gerrit_MembershipManager_CreateGroupTest extends TuleapTestCase {
    private $ugroup;
    private $logger;
    private $dao;

    public function setUp() {
        parent::setUp();

        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = mock('Git_RemoteServer_GerritServer');

        $this->git_repository_factory                = mock('GitRepositoryFactory');

        $this->logger = mock('Logger');

        $this->dao    = mock('Git_Driver_Gerrit_MembershipDao');

        $this->driver = mock('Git_Driver_Gerrit');

        $this->membership_manager = partial_mock(
            'Git_Driver_Gerrit_MembershipManager',
            array(
                'addUGroupBinding'
            ),
            array(
                $this->dao,
                $this->driver,
                $this->remote_server_factory,
                $this->logger
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

    }

    public function itCreateGroupOnAllGerritServersTheProjectUses() {
        expect($this->remote_server_factory)->getServersForProject($this->project)->once();
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));
        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itCreatesGerritGroupFromUGroup() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        expect($this->ugroup)->getLdapMembersIds(1236)->once();
        stub($this->ugroup)->getLdapMembersIds()->returns(array('ldap_id'));

        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', array('ldap_id'))->once();
        stub($this->driver)->createGroup()->returns('w3c/coders');

        $gerrit_group_name = $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
    }

    public function itStoresTheGroupInTheDb() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->remote_server)->getId()->returns(666);

        expect($this->dao)->addReference(1236, 25698, 666)->once();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itDoesntCreateAGroupThatAlreadyExist() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->doesTheGroupExist()->returns(true);
        expect($this->driver)->doesTheGroupExist($this->remote_server, 'w3c/coders')->once();

        expect($this->driver)->createGroup()->never();

        $gerrit_group_name = $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
    }

    public function itDoesntStoreInDbIfGroupAlreadyExists() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->doesTheGroupExist()->returns(true);

        expect($this->dao)->addReference()->never();

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itCreatesGerritGroupOnEachServer() {
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        stub($this->ugroup)->getLdapMembersIds()->returns(array('ldap_id'));

        expect($this->driver)->createGroup()->count(2);
        expect($this->driver)->createGroup($remote_server1, 'w3c/coders', array('ldap_id'))->at(0);
        expect($this->driver)->createGroup($remote_server2, 'w3c/coders', array('ldap_id'))->at(1);

        $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
    }

    public function itStoresTheGroupInTheDbForEachServer() {
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

        $gerrit_group_name = $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itLogsGerritExceptions() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Git_Driver_Gerrit_Exception('whatever'));

        expect($this->logger)->error('whatever')->once();

        $gerrit_group_name = $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itLogsAllOtherExceptions() {
        stub($this->remote_server_factory)->getServersForProject()->returns(array($this->remote_server));

        stub($this->driver)->createGroup()->throws(new Exception('whatever'));

        expect($this->logger)->error('Unknown error: whatever')->once();

        $gerrit_group_name = $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        $this->assertEqual($gerrit_group_name, '');
    }

    public function itContinuesToCreateGroupsEvenIfOneFails() {
        $remote_server1 = mock('Git_RemoteServer_GerritServer');
        $remote_server2 = mock('Git_RemoteServer_GerritServer');
        stub($remote_server2)->getId()->returns(667);
        stub($this->remote_server_factory)->getServersForProject()->returns(array($remote_server1, $remote_server2));

        expect($this->driver)->createGroup()->count(2);
        stub($this->driver)->createGroup()->throwsAt(0, new Exception('whatever'));
        expect($this->driver)->createGroup($remote_server2, '*', '*')->at(1);
        expect($this->dao)->addReference('*', '*', 667)->once();

        $gerrit_group_name = $this->membership_manager->createGroupOnProjectsServers($this->ugroup);
        $this->assertEqual($gerrit_group_name, 'w3c/coders');
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

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function itCreatesGroupForSpecialProjectAdminsGroup() {
        expect($this->driver)->createGroup()->once();

        $ugroup = mock('UGroup');
        stub($ugroup)->getId()->returns(UGroup::PROJECT_ADMIN);
        stub($ugroup)->getNormalizedName()->returns('project_admin');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }

    public function itCreatesAnIncludedGroupWhenUGroupIsBinded() {
        $source_group = mock('UGroup');
        
        $ugroup = mock('UGroup');
        stub($ugroup)->getId()->returns(25698);
        stub($ugroup)->getNormalizedName()->returns('coders');
        stub($ugroup)->getProject()->returns($this->project);
        stub($ugroup)->getProjectId()->returns(999);
        stub($ugroup)->getLdapMembersIds()->returns(array('ldap_id'));
        stub($ugroup)->getSourceGroup()->returns($source_group);

        expect($this->driver)->createGroup($this->remote_server, 'w3c/coders', array())->once();
        expect($this->membership_manager)->addUGroupBinding($ugroup, $source_group);

        $this->membership_manager->createGroupForServer($this->remote_server, $ugroup);
    }
}

?>
