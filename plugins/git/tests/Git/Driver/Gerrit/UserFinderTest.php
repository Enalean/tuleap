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
        $this->permissions_manager = mock('PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');
        $this->user_finder = new Git_Driver_Gerrit_UserFinder($this->permissions_manager, $this->ugroup_manager);
    }
    
    public function itReturnsNothingWhenNoGroupsHaveTheGivenPermission() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;

        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_level)->returns(array());
        $this->assertArrayEmpty($this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id));
    }
    
    public function itReturnsNothingWhenNoneOfTheGroupsHaveAnyMembers() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        
        $ugroup_id_list = array(99);
        $group1         = mock('Ugroup');
        
        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_level)->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById($ugroup_id_list[0])->returns($group1);
        stub($group1)->getMembers()->returns(array());
        $this->assertArrayEmpty($this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id));
    }
    
    public function itReturnsMembersOfAGroup() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        
        $ugroup_id_list = array(150);
        $group1         = mock('Ugroup');
        
        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById($ugroup_id_list[0])->returns($group1);
        
        $the_simpsons = array(aUser()->withId(2345)->withUserName('Bart')->build(), aUser()->withId(6789)->withUserName('Homer')->build());
        stub($group1)->getMembers()->returns($the_simpsons);
        $users = $this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id);
        $this->assertEqual($users, $the_simpsons);
    }
    
    public function itReturnsMembersOfAllGroups() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        
        $ugroup_id_list     = array(150, 152);
        $the_simpsons       = array(aUser()->withId(2345)->withUserName('Bart')->build(), aUser()->withId(5678)->withUserName('Homer')->build());
        $the_mousqueteers   = array(aUser()->withId(4444)->withUserName('Athos')->build(), aUser()->withId(5555)->withUserName('Aramis')->build());
        $group1             = stub('Ugroup')->getMembers()->returns($the_simpsons);
        $group2             = stub('Ugroup')->getMembers()->returns($the_mousqueteers);
        
        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(152)->returns($group2);
        
        $users = $this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id);
        $this->assertEqual($users, array_merge($the_mousqueteers, $the_simpsons));
    }
    
    public function itExcludesMembersOfRegisteredUsers_ToAvoidFloodingTheGerritConfig() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        
        $ugroup_id_list     = array(150, Ugroup::REGISTERED);
        $the_simpsons       = array(aUser()->withId(2345)->withUserName('Bart')->build(), aUser()->withId(6789)->withUserName('Homer')->build());
        $registered_users   = array(aUser()->withId(2345)->withUserName('Bart')->build(), aUser()->withId(6789)->withUserName('Homer')->build(), 
                                    aUser()->withId(4444)->withUserName('Athos')->build(), aUser()->withId(5555)->withUserName('Aramis')->build());
        $group1             = stub('Ugroup')->getMembers()->returns($the_simpsons);
        $group2             = stub('Ugroup')->getMembers()->returns($registered_users);
        
        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(Ugroup::REGISTERED)->returns($group2);
        
        $users = $this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id);
        $this->assertEqual($users, $the_simpsons);
    }
    
    public function itReturnsAUserOnlyOnceEvenIfHeExistInSeveralGroups() {
        $permission_level = Git::PERM_WPLUS;
        $object_id = 5;
        
        $ugroup_id_list     = array(150, 152);
        $superman           = array(aUser()->withId(2345)->withUserName('ClarkKent')->build());
        $comics_characters  = array(aUser()->withId(2345)->withUserName('ClarkKent')->build(), 
                                    aUser()->withId(6789)->withUserName('PeterParker')->build());
        $group1             = stub('Ugroup')->getMembers()->returns($superman);
        $group2             = stub('Ugroup')->getMembers()->returns($comics_characters);
        
        stub($this->permissions_manager)->getUgroupIdByObjectIdAndPermissionType($object_id, $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(152)->returns($group2);
        
        $users = $this->user_finder->getUsersForWhichTheHighestPermissionIs($permission_level, $object_id);
        $this->assertEqual($users, $comics_characters);
    }
    
    
    
    //change the method name now that we dont care about duplicating a little bit
    //non existing ugroup
    //remove anonymous group
    
    
    
    
    
}
?>
