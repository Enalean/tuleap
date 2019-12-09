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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserFinderGetUgroupsTest extends TuleapTestCase
{

    private $permissions_manager;
    private $ugroup_manager;
    private $user_finder;

    public function setUp()
    {
        parent::setUp();
        $this->permissions_manager = mock('PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');
        $this->user_finder         = new Git_Driver_Gerrit_UserFinder($this->permissions_manager, $this->ugroup_manager);
    }

    public function itAsksPermissionsToPermissionsManager()
    {
        $repository_id   = 12;
        $permission_type = GIT::PERM_READ;

        stub($this->permissions_manager)->getAuthorizedUgroups()->returnsEmptyDar();
        expect($this->permissions_manager)->getAuthorizedUgroups($repository_id, $permission_type, false)->once();

        $this->user_finder->getUgroups($repository_id, $permission_type);
    }

    public function itReturnsUGroupIdsFromPermissionsManager()
    {
        $ugroup_id_120 = 120;
        $ugroup_id_115 = 115;
        stub($this->permissions_manager)->getAuthorizedUgroups()->returnsDar(array('ugroup_id' => $ugroup_id_115), array('ugroup_id' => $ugroup_id_120));

        $ugroups = $this->user_finder->getUgroups('whatever', 'whatever');
        $this->assertEqual(
            $ugroups,
            array(
                $ugroup_id_115,
                $ugroup_id_120,
            )
        );
    }

    public function itAlwaysReturnsTheProjectAdminGroupWhenGitAdministratorsAreRequested()
    {
        $project_admin_group_id = ProjectUGroup::PROJECT_ADMIN;

        $expected_ugroups = array($project_admin_group_id);
        $ugroups          = $this->user_finder->getUgroups('whatever', Git::SPECIAL_PERM_ADMIN);

        $this->assertEqual($ugroups, $expected_ugroups);
    }

    public function itDoesntJoinWithUGroupTableWhenItFetchesGroupPermissionsInOrderToReturnSomethingWhenWeAreDeletingTheGroup()
    {
        stub($this->permissions_manager)->getAuthorizedUgroups()->returnsEmptyDar();

        expect($this->permissions_manager)->getAuthorizedUgroups('*', '*', false)->once();

        $this->user_finder->getUgroups('whatever', 'whatever');
    }
}
