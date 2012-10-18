<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once GIT_BASE_DIR.'/Git/Driver/Gerrit/UserFinder.class.php';

class Git_Driver_Gerrit_UserFinderTest extends TuleapTestCase {
    
    /** @var Git_Driver_Gerrit_UserFinder */
    protected $user_finder;
    
    /** @var PermissionsManager */
    protected $permissions_manager;
    
    /** @var UGroupManager */
    protected $ugroup_manager;
    
    public function setUp() {
        parent::setUp();
        $this->user_finder = new Git_Driver_Gerrit_UserFinder();
        $this->permissions_manager = mock('PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');
    }
    
    public function itReturnsNothingWhenNoGroupsHaveTheGivenPermission() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        //TODO better assert
        $this->assertArrayEmpty($this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id));
    }
    
    public function itReturnsNothingWhenNoneOfTheGroupsHaveAnyMembers() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        
        $ugroup_id_list = array(99);
        $group1         = mock('Ugroup');
        $user_groups    = array($group1);
        
        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($permission_level, $object_id)->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById($ugroup_id_list[0])->returns($user_groups);
        stub($group1)->getMembers()->returns(array());
        $this->assertArrayEmpty($this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id));
    }
    
    public function itReturnsMembersOfStaticGroups() {
//        $this->assert
    }
    
    
    
    
}
?>
