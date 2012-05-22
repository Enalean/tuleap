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

require_once 'common/permission/ExternalPermissions.class.php';
require_once 'common/user/User.class.php';

Mock::generate('ProjectManager');
Mock::generate('Project');

class ExternalPermissionsTest extends TuleapTestCase {
    
    protected $membership;
    protected $user_stub;
    protected $user;
    
    public function setUp() {
        parent::setUp();
        $this->user      = mock('User');
        $this->user_stub = stub($this->user);
        $userManager     = mock('UserManager');
        stub($userManager)->getUserByUserName()->returns($this->user);
        UserManager::setInstance($userManager);
    }
    
    public function tearDown() {
        parent::tearDown();
        UserManager::clearInstance();
    }
    
    public function itIsProjectMember() {
        $this->user_stub->getStatus()->returns('A');
        $userProjects = array(
                array('group_id'=>101, 'unix_group_name'=>'gpig1')
        );
        $this->user_stub->getProjects()->returns($userProjects);
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
        
        $groups   = ExternalPermissions::getUserGroups('john_do');
        $expected = array('site_active','gpig1_project_members');
        $this->assertEqual($expected, $groups);
    }
    
    
    
    public function itIsProjectAdmin() {
        $this->user_stub->getStatus()->returns('A');
        $userProjects = array(
                array('group_id'=>102, 'unix_group_name'=>'gpig2')
        );
        $this->user_stub->getProjects()->returns($userProjects);
        $this->user_stub->isMember()->returns(true);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
        
        $groups   = ExternalPermissions::getUserGroups('john_do');
        $expected = array('site_active','gpig2_project_members', 'gpig2_project_admin');
        $this->assertEqual($expected, $groups);
    }
    
    public function itIsMemberOfAStaticUgroup() {
        $this->user_stub->getStatus()->returns('A');
        $this->user_stub->getProjects()->returns(array());
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar(array('ugroup_id'=>304)));
        
        $groups   = ExternalPermissions::getUserGroups('john_do');
        $expected = array('site_active','ug_304');
        $this->assertEqual($expected, $groups);
    }
    
    public function itIsRestricted() {
        $this->user_stub->getStatus()->returns('R');
        $this->user_stub->getProjects()->returns(array());
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
        
        $groups   = ExternalPermissions::getUserGroups('john_do');
        $expected = array('site_restricted');
        $this->assertEqual($expected, $groups);
    }
    
    
    public function itIsNeitherRestrictedNorActive() {
        $this->user_stub->getStatus()->returns('Not exists');
        $this->user_stub->getProjects()->returns(array());
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
    
        $groups = ExternalPermissions::getUserGroups('john_do');
        $this->assertEqual(array(), $groups);
    }
    
}
?>