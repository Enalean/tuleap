<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/project/UGroup.class.php';

class UGroup_AddUserTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->user_id = 400;
        $this->user    = stub('User')->getId()->returns($this->user_id);
    }
    
    function itAddUserIntoStaticGroup() {
        $ugroup_id = 200;
        $group_id  = 300;
        
        $ugroup = TestHelper::getPartialMock('UGroup', array('addUserToStaticGroup', 'exists'));
        stub($ugroup)->exists()->returns(true);
        $ugroup->__construct(array('ugroup_id' => $ugroup_id, 'group_id' => $group_id));
        
        $ugroup->expectOnce('addUserToStaticGroup', array($group_id, $ugroup_id, $this->user_id));
        
        $ugroup->addUser($this->user);
    }
    
    function itThrowAnExceptionIfStaticUGroupDoesntExist() {
        $ugroup_id = 200;
        $group_id  = 300;
        
        $ugroup = TestHelper::getPartialMock('UGroup', array('exists'));
        stub($ugroup)->exists()->returns(false);
        $ugroup->__construct(array('ugroup_id' => $ugroup_id, 'group_id' => $group_id));
        
        $this->expectException(new UGroup_Invalid_Exception());
        
        $ugroup->addUser($this->user);
    }
    
    function itAddUserIntoDynamicGroup() {
        $ugroup_id = $GLOBALS['UGROUP_WIKI_ADMIN'];
        $group_id  = 300;
        
        $ugroup = TestHelper::getPartialMock('UGroup', array('_getUserGroupDao'));
        
        $dao = mock('UserGroupDao');
        stub($ugroup)->_getUserGroupDao()->returns($dao);
        
        $ugroup->__construct(array('ugroup_id' => $ugroup_id, 'group_id' => $group_id));
        
        
        $dao->expectOnce('updateUserGroupFlags', array($this->user_id, $group_id, 'wiki_flags = 2'));
        
        $ugroup->addUser($this->user);
    }
    
    function itThrowAnExceptionIfThereIsNoGroupId() {
        $ugroup_id = 200;
        
        $ugroup = new UGroup(array('ugroup_id' => $ugroup_id));
        
        $this->expectException();
        
        $ugroup->addUser($this->user);
    }
    
    function itThrowAnExceptionIfThereIsNoUGroupId() {
        $group_id  = 300;
        
        $ugroup = new UGroup(array('group_id' => $group_id));
        
        $this->expectException();
        
        $ugroup->addUser($this->user);
    }

    function itThrowAnExceptionIfUserIsNotValid() {
        $group_id  = 300;
        $ugroup_id = 200;
        
        $ugroup = new UGroup(array('group_id' => $group_id, 'ugroup_id' => $ugroup_id));
        
        $this->expectException();
        
        $user = anAnonymousUser()->build();
        
        $ugroup->addUser($user);
    }
}

class UGroup_DynamicGroupTest extends TuleapTestCase {
    function itConvertDynamicGroupIdToCorrespondingDatabaseField() {
        //$this->assertEqual(UGroup::getFieldForUGroupId())
        $this->assertEqual(UGroup::getAddFlagForUGroupId($GLOBALS['UGROUP_PROJECT_ADMIN']),      "admin_flags = 'A'");
        $this->assertEqual(UGroup::getAddFlagForUGroupId($GLOBALS['UGROUP_FILE_MANAGER_ADMIN']), 'file_flags = 2');
        $this->assertEqual(UGroup::getAddFlagForUGroupId($GLOBALS['UGROUP_WIKI_ADMIN']),         'wiki_flags = 2');
                //$this->assertEqual(UGroup::getFieldForUGroupId($GLOBALS['UGROUP_DOCUMENT_TECH'], 'doc_flags = '));
        //$this->assertEqual(UGroup::getFieldForUGroupId($GLOBALS['UGROUP_DOCUMENT_ADMIN'], ''));
        ////forum admin
        /// SVN
        // News
    }
}

?>
