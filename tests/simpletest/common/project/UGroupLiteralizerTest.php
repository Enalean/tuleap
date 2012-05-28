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

require_once 'common/project/UGroupLiteralizer.class.php';

class UGroupLiteralizerTest extends TuleapTestCase {
    
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
        $this->ugroup_literalizer = new UGroupLiteralizer();
    }
    
    public function tearDown() {
        UserManager::clearInstance();
        parent::tearDown();
    }
    
    public function itIsProjectMember() {
        $this->user_stub->getStatus()->returns('A');
        $userProjects = array(
                array('group_id'=>101, 'unix_group_name'=>'gpig1')
        );
        $this->user_stub->getProjects()->returns($userProjects);
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
        
        $groups   = $this->ugroup_literalizer->getUserGroupsForUserName('john_do');
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
        
        $groups   = $this->ugroup_literalizer->getUserGroupsForUserName('john_do');
        $expected = array('site_active','gpig2_project_members', 'gpig2_project_admin');
        $this->assertEqual($expected, $groups);
    }
    
    public function itIsMemberOfAStaticUgroup() {
        $this->user_stub->getStatus()->returns('A');
        $this->user_stub->getProjects()->returns(array());
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar(array('ugroup_id'=>304)));
        
        $groups   = $this->ugroup_literalizer->getUserGroupsForUserName('john_do');
        $expected = array('site_active','ug_304');
        $this->assertEqual($expected, $groups);
    }
    
    public function itIsRestricted() {
        $this->user_stub->getStatus()->returns('R');
        $this->user_stub->getProjects()->returns(array());
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
        
        $groups   = $this->ugroup_literalizer->getUserGroupsForUserName('john_do');
        $expected = array('site_restricted');
        $this->assertEqual($expected, $groups);
    }
    
    
    public function itIsNeitherRestrictedNorActive() {
        $this->user_stub->getStatus()->returns('Not exists');
        $this->user_stub->getProjects()->returns(array());
        $this->user_stub->isMember()->returns(false);
        $this->user_stub->getAllUgroups()->returns(TestHelper::arrayToDar());
    
        $groups = $this->ugroup_literalizer->getUserGroupsForUserName('john_do');
        $this->assertEqual(array(), $groups);
    }
}
?>
