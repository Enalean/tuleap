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
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit.class.php';
require_once 'common/include/Config.class.php';

class Git_Driver_Gerrit_MembershipManagerTest extends TuleapTestCase {
    private $membership_manager;
    private $driver;
    private $git_repository_factory;
    private $git_repository_factory_without_gerrit;
    private $permissions_manager;

    public function setUp() {

        $this->driver = mock('Git_Driver_Gerrit');
        $this->permissions_manager = mock('PermissionsManager');
        $this->remote_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->remote_server = mock ('Git_RemoteServer_GerritServer');

        stub($this->remote_server_factory)->getServer()->returns($this->remote_server);

    }

    public function itDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit() {
        $user    = aUser()->build();
        $project = mock('Project');
        $u_group = mock('UGroup');

        $this->git_repository_factory_without_gerrit = mock('GitRepositoryFactory');
        stub($this->git_repository_factory_without_gerrit)->getAllRepositories($project)->returns(array());

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager($this->git_repository_factory_without_gerrit, $this->driver, $this->permissions_manager, $this->remote_server_factory);

        expect($this->driver)->addUserToGroup()->never();

        $this->membership_manager->addUserToGroup($user, $u_group, $project);
    }

    public function itAsksForAllTheRepositoriesOfAProject() {
        $user    = aUser()->build();
        $project = mock('Project');
        $u_group = mock('UGroup');

        $this->git_repository_factory = mock('GitRepositoryFactory');
        stub($this->git_repository_factory)->getAllRepositories($project)->returns(array());

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager($this->git_repository_factory, $this->driver, $this->permissions_manager, $this->remote_server_factory);

        expect($this->git_repository_factory)->getAllRepositories($project)->once();

        $this->membership_manager->addUserToGroup($user, $u_group, $project);
    }

    public function itAsksTheGerritDriverToAddAUserToThreeGroups() {
        $user                 = aUser()->build();
        $project              = mock('Project');
        $u_group              = mock('UGroup');
        $git_repository       = mock('GitRepository');
        $git_repository_id    = 20;
        $permissions_manager  = mock('PermissionsManager');

        stub($project)->getUnixName()->returns('someProject');
        stub($git_repository)->getFullName()->returns('some/git/project');
        stub($git_repository)->isMigratedToGerrit()->returns(true);
        stub($git_repository)->getId()->returns($git_repository_id);

        stub($permissions_manager)->userHasPermission($git_repository_id, Git::PERM_READ, $u_group)->returns(true);
        stub($permissions_manager)->userHasPermission($git_repository_id, Git::PERM_WRITE, $u_group)->returns(true);
        stub($permissions_manager)->userHasPermission($git_repository_id, Git::PERM_WPLUS, $u_group)->returns(true);
        stub($permissions_manager)->userHasPermission($git_repository_id, Git::SPECIAL_PERM_ADMIN, $u_group)->returns(false);

        $first_group_expected = 'someProject/some/git/project-contributors';
        $second_group_expected = 'someProject/some/git/project-integrators';
        $third_group_expected = 'someProject/some/git/project-supermen';

        $this->git_repository_factory = mock('GitRepositoryFactory');
        stub($this->git_repository_factory)->getAllRepositories($project)->returns(array($git_repository));

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager($this->git_repository_factory, $this->driver, $permissions_manager, $this->remote_server_factory);

        $this->driver->expectCallCount('addUserToGroup', 3);
        expect($this->driver)->addUserToGroup($this->remote_server, $user, $first_group_expected)->at(0);
        expect($this->driver)->addUserToGroup($this->remote_server, $user, $second_group_expected)->at(1);
        expect($this->driver)->addUserToGroup($this->remote_server, $user, $third_group_expected)->at(2);

        $this->membership_manager->addUserToGroup($user, $u_group, $project);
    }
}
?>
