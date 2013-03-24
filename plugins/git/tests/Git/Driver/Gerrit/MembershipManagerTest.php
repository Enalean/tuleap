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
        $this->git_repository_factory                = mock('GitRepositoryFactory');
        $this->git_repository                        = mock('GitRepository');
        $this->membership_command_add                = new Git_Driver_Gerrit_MembershipCommand_AddUser($this->driver, $this->user_finder);
        $this->membership_command_remove             = new Git_Driver_Gerrit_MembershipCommand_RemoveUser($this->driver, $this->user_finder);

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->project)->getUnixName()->returns($this->project_name);
    }
}

class Git_Driver_Gerrit_MembershipManager_NoGerritRepoTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {
    protected $git_repository_factory_without_gerrit;

    public function setUp() {
        parent::setUp();

        $this->git_repository_factory_without_gerrit = mock('GitRepositoryFactory');
        stub($this->git_repository_factory_without_gerrit)->getGerritRepositoriesWithPermissionsForUGroup()->returns(array());

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->git_repository_factory_without_gerrit,
            $this->remote_server_factory
        );
    }

    public function itAsksForAllTheRepositoriesOfAProject() {
        expect($this->git_repository_factory_without_gerrit)->getGerritRepositoriesWithPermissionsForUGroup($this->project, $this->u_group, $this->user)->once();

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_add);
    }

    public function itDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit() {
        expect($this->driver)->addUserToGroup()->never();
        expect($this->driver)->removeUserFromGroup()->never();

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_add);
    }

}

abstract class Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest extends Git_Driver_Gerrit_MembershipManagerCommonTest {
    public function setUp() {
        parent::setUp();

        stub($this->git_repository)->getFullName()->returns($this->git_repository_name);
        stub($this->git_repository)->getId()->returns($this->git_repository_id);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->git_repository_factory,
            $this->remote_server_factory
        );
    }
}

class Git_Driver_Gerrit_MembershipManagerTest extends Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest {

    public function setUp() {
        parent::setUp();

        $git_permissions = new GitRepositoryWithPermissions(
            $this->git_repository,
            array(
                Git::PERM_READ          => array($this->u_group_id),
                Git::PERM_WRITE         => array($this->u_group_id),
                Git::PERM_WPLUS         => array($this->u_group_id),
                Git::SPECIAL_PERM_ADMIN => array(),
            )
        );

        stub($this->git_repository_factory)->getGerritRepositoriesWithPermissionsForUGroup()->returns(array($git_permissions));
    }

    public function itAsksTheGerritDriverToAddAUserToThreeGroups() {
        stub($this->user)->getUgroups()->returns(array($this->u_group_id));

        $gerrit_group_name_prefix = $this->project_name.'/'.$this->git_repository_name;
        $first_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_CONTRIBUTORS;
        $second_group_expected    = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_INTEGRATORS;
        $third_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_SUPERMEN;

        $this->driver->expectCallCount('addUserToGroup', 3);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_add);
    }

    public function itAsksTheGerritDriverToRemoveAUserFromThreeGroups() {
        stub($this->user)->getUgroups()->returns(array());

        $gerrit_group_name_prefix = $this->project_name.'/'.$this->git_repository_name;
        $first_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_CONTRIBUTORS;
        $second_group_expected    = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_INTEGRATORS;
        $third_group_expected     = $gerrit_group_name_prefix.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_SUPERMEN;

        $this->driver->expectCallCount('removeUserFromGroup', 3);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_remove);
    }

    public function itDoesntAddNonLDAPUsersToGerrit() {
        $non_ldap_user = mock('PFUser');
        stub($non_ldap_user)->getUgroups()->returns(array($this->u_group_id));

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->updateUserMembership($non_ldap_user, $this->u_group, $this->project, $this->membership_command_add);
    }
}

class Git_Driver_Gerrit_MembershipManager_SeveralUGroupsTest extends Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest {
    private $u_group_id_120 = 120;

    public function setUp() {
        parent::setUp();

        $git_permissions = new GitRepositoryWithPermissions(
            $this->git_repository,
            array(
                Git::PERM_READ          => array($this->u_group_id_120, $this->u_group_id),
                Git::PERM_WRITE         => array($this->u_group_id_120),
                Git::PERM_WPLUS         => array(),
                Git::SPECIAL_PERM_ADMIN => array(),
            )
        );

        stub($this->git_repository_factory)->getGerritRepositoriesWithPermissionsForUGroup()->returns(array($git_permissions));
    }

    public function itDoesntRemoveUserIfTheyBelongToAtLeastOneGroupThatHaveAccess() {
        // User was removed from ugroup 115 but is still member of ugroup 120
        stub($this->user)->getUgroups()->returns(array($this->u_group_id_120));
        expect($this->driver)->removeUserFromGroup()->never();
        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_remove);
    }
}

class Git_Driver_Gerrit_MembershipManager_ProjectAdminTest extends Git_Driver_Gerrit_MembershipManagerCommonWithRepoTest {

    public function setUp() {
        parent::setUp();

        $git_permissions = new GitRepositoryWithPermissions(
            $this->git_repository,
            array(
                Git::PERM_READ          => array(),
                Git::PERM_WRITE         => array(),
                Git::PERM_WPLUS         => array(),
                Git::SPECIAL_PERM_ADMIN => array(),
            )
        );

        $this->admin_ugroup = mock('UGroup');
        stub($this->admin_ugroup)->getId()->returns(UGroup::PROJECT_ADMIN);

        stub($this->user)->getUgroups()->returns(array($this->u_group_id, UGroup::PROJECT_ADMIN));

        stub($this->git_repository_factory)->getGerritRepositoriesWithPermissionsForUGroup()->returns(array($git_permissions));
    }

    public function itProcessesTheListOfGerritRepositoriresWhenWeModifyProjectAdminGroup() {
        expect($this->git_repository_factory)->getAllGerritRepositoriesFromProject($this->project, $this->user)->once();
        stub($this->git_repository_factory)->getAllGerritRepositoriesFromProject()->returns(array());

        $this->membership_manager->updateUserMembership($this->user, $this->admin_ugroup, $this->project, $this->membership_command_add);
    }

    public function itUpdatesGerritProjectOwnersWhenIAddANewProjectAdmin() {
        stub($this->git_repository_factory)->getAllGerritRepositoriesFromProject()->returns(array(
            new GitRepositoryWithPermissions(
                $this->git_repository,
                array(
                    Git::PERM_READ          => array(),
                    Git::PERM_WRITE         => array(),
                    Git::PERM_WPLUS         => array(),
                    Git::SPECIAL_PERM_ADMIN => array(UGroup::PROJECT_ADMIN),
                )
            )
        ));

        $gerrit_project_owners_group_name = $this->project_name.'/'.$this->git_repository_name.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_OWNERS;
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $gerrit_project_owners_group_name)->once();

        $this->membership_manager->updateUserMembership($this->user, $this->admin_ugroup, $this->project, $this->membership_command_add);
    }

    public function itUpdatesAllGerritGroupsWhenIAddANewProjectAdmin() {
        stub($this->git_repository_factory)->getAllGerritRepositoriesFromProject()->returns(array(
            new GitRepositoryWithPermissions(
                $this->git_repository,
                array(
                    Git::PERM_READ          => array(),
                    Git::PERM_WRITE         => array(UGroup::PROJECT_ADMIN),
                    Git::PERM_WPLUS         => array(),
                    Git::SPECIAL_PERM_ADMIN => array(UGroup::PROJECT_ADMIN),
                )
            )
        ));

        expect($this->driver)->addUserToGroup()->count(2);
        $gerrit_project_integrators_group_name = $this->project_name.'/'.$this->git_repository_name.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_INTEGRATORS;
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $gerrit_project_integrators_group_name)->at(0);
        $gerrit_project_owners_group_name = $this->project_name.'/'.$this->git_repository_name.'-'.Git_Driver_Gerrit_MembershipManager::GROUP_OWNERS;
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $gerrit_project_owners_group_name)->at(1);

        $this->membership_manager->updateUserMembership($this->user, $this->admin_ugroup, $this->project, $this->membership_command_add);
    }
}

?>
