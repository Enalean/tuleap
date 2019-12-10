<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

class UserFinderTest extends TuleapTestCase
{

    /** @var Git_Driver_Gerrit_UserFinder */
    protected $user_finder;

    /** @var PermissionsManager */
    protected $permissions_manager;

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var GitRepository **/
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $this->permissions_manager = mock('PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');
        $this->user_finder = new Git_Driver_Gerrit_UserFinder($this->permissions_manager, $this->ugroup_manager);
        $this->project_id = 666;
        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(5);
        stub($this->repository)->getProjectId()->returns($this->project_id);
    }

    public function itReturnsFalseForSpecialAdminPerms()
    {
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::SPECIAL_PERM_ADMIN, $this->repository);
        $this->assertFalse($allowed);
    }

    public function itReturnsFalseIfRegisteredUsersGroupIsNotContainedInTheAllowedOnes()
    {
        stub($this->permissions_manager)->getAuthorizedUgroups()->returns(array(
            array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS),
            array('ugroup_id' => ProjectUGroup::PROJECT_ADMIN),
        ));
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        $this->assertFalse($allowed);
    }

    public function itReturnsTrueIfRegisteredUsersGroupIsContainedInTheAllowedOnes()
    {
        stub($this->permissions_manager)->getAuthorizedUgroups()->returns(array(
            array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS),
            array('ugroup_id' => ProjectUGroup::REGISTERED),
        ));
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        $this->assertTrue($allowed);
    }

    public function itReturnsTrueIfAllUsersAreContainedInTheAllowedOnes()
    {
        stub($this->permissions_manager)->getAuthorizedUgroups()->returns(array(
            array('ugroup_id' => ProjectUGroup::ANONYMOUS),
        ));
        $allowed = $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository);
        $this->assertTrue($allowed);
    }
}
