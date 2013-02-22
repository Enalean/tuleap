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
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit/MembershipManager.class.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit.class.php';
require_once 'common/include/Config.class.php';

class Git_Driver_Gerrit_MembershipManagerTest extends TuleapTestCase {
    private $membership_manager;
    private $driver;
    private $git_repository_factory_without_gerrit;

    public function setUp() {

        $this->driver = mock('Git_Driver_Gerrit');

    }

    public function itDoesNotCallTheGerritDriverIfNoneOfTheRepositoriesAreUnderGerrit() {
        $user    = aUser()->build();
        $project = mock('Project');
        $u_group = mock('UGroup');

        $this->git_repository_factory_without_gerrit = mock('GitRepositoryFactory');
        stub($this->git_repository_factory_without_gerrit)->getAllRepositories($project)->returns(array());

        $this->membership_manager = new Git_Driver_Gerrit_MembershipManager($this->git_repository_factory_without_gerrit);

        expect($this->driver)->addUserToGroup()->never();
        expect($this->git_repository_factory_without_gerrit)->getAllRepositories($project)->once();

        $this->membership_manager->addUserToGroup($user, $u_group, $project);
    }
}
?>
