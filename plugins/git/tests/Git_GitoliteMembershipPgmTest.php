<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../include/Git_GitoliteMembershipPgm.class.php';
require_once 'common/user/User.class.php';

Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('UserManager');
Mock::generate('User');

class Git_GitoliteMembershipPgmTest extends TuleapTestCase {
    
    protected $membership;
    protected $user;
    
    public function setUp() {
        parent::setUp();
        $this->user = new MockUser();
        $userManager = new MockUserManager();
        $userManager->setReturnValue('getUserByUserName', $this->user);
        UserManager::setInstance($userManager);
        $this->membership = new Git_GitoliteMembershipPgm();
    }
    
    public function tearDown() {
        parent::tearDown();
        UserManager::clearInstance();
    }
    
    public function itIsProjectMember() {
        $this->user->setReturnValue('getStatus', 'A');
        $userProjects = array(
                array('group_id'=>101, 'unix_group_name'=>'gpig1')
        );
        $this->user->setReturnValue('getProjects', $userProjects);
        $this->user->setReturnValue('isMember', false);
        $this->user->setReturnValue('getAllUgroups', TestHelper::arrayToDar());
        
        $groups = $this->membership->getGroups('john_do');
        $expected = array('site_active','gpig1_project_members');
        $this->assertEqual($expected, $groups);
    }
    
    
    
    public function itIsProjectAdmin() {
        $this->user->setReturnValue('getStatus', 'A');
        $userProjects = array(
                array('group_id'=>102, 'unix_group_name'=>'gpig2')
        );
        $this->user->setReturnValue('getProjects', $userProjects);
        $this->user->setReturnValue('isMember', true);
        $this->user->setReturnValue('getAllUgroups', TestHelper::arrayToDar());
        
        $groups   = $this->membership->getGroups('john_do');
        $expected = array('site_active','gpig2_project_members', 'gpig2_project_admin');
        $this->assertEqual($expected, $groups);
    }
    
    public function itIsMemberOfAStaticUgroup() {
        $this->user->setReturnValue('getStatus', 'A');
        $this->user->setReturnValue('getProjects', array());
        $this->user->setReturnValue('isMember', false);
        $this->user->setReturnValue('getAllUgroups', TestHelper::arrayToDar(array('ugroup_id'=>304)));
        
        $groups   = $this->membership->getGroups('john_do');
        $expected = array('site_active','ug_304');
        $this->assertEqual($expected, $groups);
    }
    
    public function itIsRestricted() {
        $this->user->setReturnValue('getStatus', 'R');
        $this->user->setReturnValue('getProjects', array());
        $this->user->setReturnValue('isMember', false);
        $this->user->setReturnValue('getAllUgroups', TestHelper::arrayToDar());
        
        $groups   = $this->membership->getGroups('john_do');
        $expected = array('site_restricted');
        $this->assertEqual($expected, $groups);
    }
    
    
    public function itIsNeitherRestrictedNorActive() {
        $this->user->setReturnValue('getStatus', 'Not exists');
        $this->user->setReturnValue('getProjects', array());
        $this->user->setReturnValue('isMember', false);
        $this->user->setReturnValue('getAllUgroups', TestHelper::arrayToDar());
    
        $groups = $this->membership->getGroups('john_do');
        $this->assertEqual(array(), $groups);
    }
    
}
?>