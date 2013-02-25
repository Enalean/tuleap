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

class Git_Driver_Gerrit_MembershipManagerTest extends TuleapTestCase {
    private $membership_manager;
    private $driver;
    private $git_repository_factory;
    private $git_repository_factory_without_gerrit;
    private $permissions_manager;
    private $user;
    private $project;
    private $u_group;
    private $git_repository;
    private $membership_command;

    public function setUp() {

        $this->user                                  = aUser()->build();
        $this->driver                                = mock('Git_Driver_Gerrit');
        $this->permissions_manager                   = mock('PermissionsManager');
        $this->remote_server_factory                 = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server                         = mock ('Git_RemoteServer_GerritServer');
        $this->project                               = mock('Project');
        $this->u_group                               = mock('UGroup');
        $this->git_repository_factory_without_gerrit = mock('GitRepositoryFactory');
        $this->git_repository_factory                = mock('GitRepositoryFactory');
        $this->git_repository                        = mock('GitRepository');
        $this->membership_command_add                = new Git_Driver_Gerrit_MembershipCommand_AddUser($this->driver);
        $this->membership_command_remove             = new Git_Driver_Gerrit_MembershipCommand_RemoveUser($this->driver);

        $this->git_repository_id    = 20;

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);
        stub($this->git_repository_factory)->getAllRepositories($this->project)->returns(array($this->git_repository));
        stub($this->git_repository_factory_without_gerrit)->getAllRepositories($this->project)->returns(array());
        stub($this->project)->getUnixName()->returns('someProject');
        stub($this->git_repository)->getFullName()->returns('some/git/project');
        stub($this->git_repository)->getId()->returns($this->git_repository_id);

        stub($this->permissions_manager)->userHasPermission($this->git_repository_id, Git::PERM_READ, $this->u_group)->returns(true);
        stub($this->permissions_manager)->userHasPermission($this->git_repository_id, Git::PERM_WRITE, $this->u_group)->returns(true);
        stub($this->permissions_manager)->userHasPermission($this->git_repository_id, Git::PERM_WPLUS, $this->u_group)->returns(true);
        stub($this->permissions_manager)->userHasPermission($this->git_repository_id, Git::SPECIAL_PERM_ADMIN, $this->u_group)->returns(false);

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->git_repository_factory,
            $this->driver,
            $this->permissions_manager,
            $this->remote_server_factory
        );
    }

    public function itDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit() {
        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager(
            $this->git_repository_factory_without_gerrit,
            $this->driver,
            $this->permissions_manager,
            $this->remote_server_factory
        );

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_add);
    }

    public function itAsksForAllTheRepositoriesOfAProject() {
        expect($this->git_repository_factory)->getAllRepositories($this->project)->once();

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_add);
    }

    public function itAsksTheGerritDriverToAddAUserToThreeGroups() {
        stub($this->git_repository)->isMigratedToGerrit()->returns(true);

        $first_group_expected = 'someProject/some/git/project-contributors';
        $second_group_expected = 'someProject/some/git/project-integrators';
        $third_group_expected = 'someProject/some/git/project-supermen';

        $this->driver->expectCallCount('addUserToGroup', 3);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->addUserToGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_add);
    }

    public function itAsksTheGerritDriverToRemoveAUserFromThreeGroups() {
        stub($this->git_repository)->isMigratedToGerrit()->returns(true);

        $first_group_expected = 'someProject/some/git/project-contributors';
        $second_group_expected = 'someProject/some/git/project-integrators';
        $third_group_expected = 'someProject/some/git/project-supermen';

        $this->driver->expectCallCount('removeUserFromGroup', 3);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $first_group_expected)->at(0);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $second_group_expected)->at(1);
        expect($this->driver)->removeUserFromGroup($this->remote_server, $this->user, $third_group_expected)->at(2);

        $this->membership_manager->updateUserMembership($this->user, $this->u_group, $this->project, $this->membership_command_remove);

    }
}
?>
