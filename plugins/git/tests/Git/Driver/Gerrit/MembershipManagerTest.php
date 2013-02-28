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
    protected $git_repository_factory;
    protected $git_repository_factory_without_gerrit;
    protected $user_finder;
    protected $user;
    protected $project_name = 'someProject';
    protected $project;
    protected $u_group;
    protected $git_repository_name = 'some/git/project';
    protected $git_repository;
    protected $membership_command_add;
    protected $membership_command_remove;

    public function setUp() {
        $this->user                                  = stub('User')->getLdapId()->returns('whatever');
        $this->driver                                = mock('Git_Driver_Gerrit');
        $this->user_finder                           = mock('Git_Driver_Gerrit_UserFinder');
        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = mock ('Git_RemoteServer_GerritServer');
        $this->project                               = mock('Project');
        $this->u_group                               = 115;
        $this->git_repository_factory_without_gerrit = mock('GitRepositoryFactory');
        $this->git_repository_factory                = mock('GitRepositoryFactory');
        $this->git_repository                        = mock('GitRepository');
        $this->membership_command_add                = new Git_Driver_Gerrit_MembershipCommand_AddUser($this->driver, $this->user_finder);
        $this->membership_command_remove             = new Git_Driver_Gerrit_MembershipCommand_RemoveUser($this->driver, $this->user_finder);

        $this->git_repository_id    = 20;

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->git_repository_factory)->getAllRepositories($this->project)->returns(array($this->git_repository));
        stub($this->git_repository_factory_without_gerrit)->getAllRepositories($this->project)->returns(array());
        stub($this->project)->getUnixName()->returns($this->project_name);
        stub($this->git_repository)->getFullName()->returns($this->git_repository_name);
        stub($this->git_repository)->getId()->returns($this->git_repository_id);


        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->git_repository_factory,
            $this->remote_server_factory
        );
    }
}

class Git_Driver_Gerrit_MembershipManagerTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {

    public function setUp() {
        parent::setUp();

        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::PERM_READ)->returns(array($this->u_group));
        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::PERM_WRITE)->returns(array($this->u_group));
        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::PERM_WPLUS)->returns(array($this->u_group));
        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::SPECIAL_PERM_ADMIN)->returns(array());
    }

    public function itDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit() {
        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->git_repository_factory_without_gerrit,
            $this->remote_server_factory
        );

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->updateUserMembership($this->user, $this->project, $this->membership_command_add);
    }

    public function itAsksForAllTheRepositoriesOfAProject() {
        expect($this->git_repository_factory)->getAllRepositories($this->project)->once();

        $this->membership_manager->updateUserMembership($this->user, $this->project, $this->membership_command_add);
    }

    public function itAsksTheGerritDriverToAddAUserToThreeGroups() {
        stub($this->git_repository)->isMigratedToGerrit()->returns(true);

        stub($this->user)->getUgroups()->returns(array($this->u_group));

        $gerrit_group_name_prefix = $this->project_name.'/'.$this->git_repository_name;
        $first_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_CONTRIBUTORS;
        $second_group_expected    = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_INTEGRATORS;
        $third_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_SUPERMEN;

        $this->driver->expectCallCount('addUserToGroup', 3);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

        $this->membership_manager->updateUserMembership($this->user, $this->project, $this->membership_command_add);
    }

    public function itAsksTheGerritDriverToRemoveAUserFromThreeGroups() {
        stub($this->git_repository)->isMigratedToGerrit()->returns(true);

        stub($this->user)->getUgroups()->returns(array());

        $gerrit_group_name_prefix = $this->project_name.'/'.$this->git_repository_name;
        $first_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_CONTRIBUTORS;
        $second_group_expected    = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_INTEGRATORS;
        $third_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_SUPERMEN;

        $this->driver->expectCallCount('removeUserFromGroup', 3);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

        $this->membership_manager->updateUserMembership($this->user, $this->project, $this->membership_command_remove);
    }

    public function itDoesntAddNonLDAPUsersToGerrit() {
        stub($this->git_repository)->isMigratedToGerrit()->returns(true);

        $non_ldap_user = mock('User');
        stub($non_ldap_user)->getUgroups()->returns(array($this->u_group));

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->updateUserMembership($non_ldap_user, $this->project, $this->membership_command_add);
    }
}

class Git_Driver_Gerrit_MembershipManager_SeveralUGroupsTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {
    private $u_group_id_120 = 120;

    public function setUp() {
        parent::setUp();

        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::PERM_READ)->returns(array($this->u_group_id_120, $this->u_group));
        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::PERM_WRITE)->returns(array($this->u_group_id_120));
        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::PERM_WPLUS)->returns(array());
        stub($this->user_finder)->getUgroups($this->git_repository_id, Git::SPECIAL_PERM_ADMIN)->returns(array());

        stub($this->git_repository)->isMigratedToGerrit()->returns(true);
    }

    public function itDoesntRemoveUserIfTheyBelongToAtLeastOneGroupThatHaveAccess() {
        // User was removed from ugroup 115 but is still member of ugroup 120
        stub($this->user)->getUgroups()->returns(array($this->u_group_id_120));
        expect($this->driver)->removeUserFromGroup()->never();
        $this->membership_manager->updateUserMembership($this->user, $this->project, $this->membership_command_remove);
    }

}
?>
