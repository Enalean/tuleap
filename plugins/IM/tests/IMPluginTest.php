<?php

require_once('common/language/BaseLanguage.class.php');

require_once(dirname(__FILE__).'/../include/IMPlugin.class.php');
//require_once(dirname(__FILE__).'/../include/jabbex_api/Jabbex.php');

require_once('common/project/Project.class.php');
Mock::generate('Project');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');

require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');

require_once(dirname(__FILE__).'/../include/jabbex_api/Jabbex.php');
Mock::generate('Jabbex');

Mock::generatePartial(
    'IMPlugin',
    'IMPluginTestVersion',
    array('project_get_object', 'getMembersId', 'user_getrealname', '_this_muc_exist', '_get_im_object', 'getUserManager', 'getProjectManager')
);


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class IMPlugin
 */
class IMPluginTest extends UnitTestCase {
    
    var $implugin;
    var $project;
    var $project_manager;
    var $user;
    var $user_manager;
    var $jabbex;

    function setUp() {
        $this->implugin = new IMPluginTestVersion($this);
        
        $this->project = new MockProject($this);
        $this->project->setReturnValue('getUnixName', 'mockproject');
        $this->project->setReturnValue('getPublicName', 'My Mock Project');
        $this->project->setReturnValue('getDescription', 'Description of my Mock Project');
        $this->project->setReturnValue('getMembersId', array(125, 456));
        $this->project_manager = new MockProjectManager($this);
        $this->project_manager->setReturnReference('getProject', $this->project);
        $this->implugin->setReturnReference('getProjectManager', $this->project_manager);
        
        $this->user = mock('PFUser');
        $this->user->setReturnValue('getName', 'mockuser');
        $this->user_manager = new MockUserManager($this);
        $this->user_manager->setReturnReference('getUserById', $this->user);
        $this->implugin->setReturnReference('getUserManager', $this->user_manager);
        
        $this->jabbex = new MockJabbex($this);
        $this->implugin->setReturnValue('_get_im_object', $this->jabbex);
        
    }
    
    function tearDown() {
        unset($this->implugin);
        unset($this->project);
        unset($this->project_manager);
        unset($this->user);
        unset($this->user_manager);
        unset($this->jabbex);
    }
    
    function testProjectIsApproved() {
        // Test that projectIsApproved hook call create_muc_room and create_shared_group Jabbex functions
        $this->implugin->setReturnValue('_this_muc_exist', false);
        // muc_room_creation
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectOnce('create_muc_room', array('mockproject', 'My Mock Project', 'Description of my Mock Project', 'mockuser'));
        // create_im_shared_group
        $this->jabbex->expectOnce('create_shared_group', array('mockproject', 'My Mock Project'));
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsApproved($params);
    }
    
    function testProjectIsApprovedMucRoomAlreadyExist() {
        // same test than testProjectIsApproved but muc room already exists
        $this->implugin->setReturnValue('_this_muc_exist', true);
        // muc_room_creation
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectCallCount('create_muc_room', 0);
        // create_im_shared_group
        $this->jabbex->expectOnce('create_shared_group', array('mockproject', 'My Mock Project'));
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsApproved($params);
    }
    
    function testProjectIsSuspendedOrPending() {
        // Test that projectIsSuspendingOrPending hook call lock_muc_room Jabbex function
        $this->implugin->setReturnValue('_this_muc_exist', true);
        // lock_muc_room
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectOnce('lock_muc_room', array('mockproject'));
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsSuspendedOrPending($params);
    }

    function testProjectIsSuspendedOrPendingMucRoomDoesNotExist() {
        // same test than testProjectIsSuspendedOrPending but muc room does not exists
        $this->implugin->setReturnValue('_this_muc_exist', false);
        // lock_muc_room
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectCallCount('lock_muc_room', 0);
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsSuspendedOrPending($params);
    }
    
    function testProjectIsDeleted() {
        // Test that projectIsDeleted hook call lock_muc_room Jabbex function
        $this->implugin->setReturnValue('_this_muc_exist', true);
        // lock_muc_room
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectOnce('lock_muc_room', array('mockproject'));
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsDeleted($params);
    }

    function testProjectIsDeletedMucRoomDoesNotExist() {
        // same test than testProjectIsDeleted but muc room does not exists
        $this->implugin->setReturnValue('_this_muc_exist', false);
        // lock_muc_room
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectCallCount('lock_muc_room', 0);
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsDeleted($params);
    }
    
    function testProjectIsActive() {
        // Test that projectIsActive hook call unlock_muc_room Jabbex function
        $this->implugin->setReturnValue('_this_muc_exist', true);
        // lock_muc_room
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectOnce('unlock_muc_room', array('mockproject'));
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsActive($params);
    }
    
    function testProjectIsActiveMucRoomDoesNotExist() {
        // same test than testProjectIsActive but muc room does not exists
        $this->implugin->setReturnValue('_this_muc_exist', false);
        // lock_muc_room
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectCallCount('unlock_muc_room', 0);
        
        $params = array('group_id' => 1);
        $this->implugin->projectIsActive($params);
    }
    
    function testProjectAddUser() {
        // Test that projectAddMember hook call muc_add_member Jabbex function
        $this->implugin->setReturnValue('_this_muc_exist', true);
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectOnce('muc_add_member', array('mockproject', 'mockuser1'));
        
        $params = array('user_unix_name' => 'mockuser1', 'group_id' => 1);
        $this->implugin->projectAddUser($params);
    }
    
    function testProjectAddUserMucRoomDoesNotExist() {
        // same test than testProjectAddUser but muc room does not exists
        $this->implugin->setReturnValue('_this_muc_exist', false);
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectCallCount('muc_add_member', 0);
        
        $params = array('user_unix_name' => 'mockuser1', 'group_id' => 1);
        $this->implugin->projectAddUser($params);
    }
    
    function testProjectRemoveUser() {
        // Test that projectRemoveMember hook call muc_remove_member Jabbex function
        $this->implugin->setReturnValue('_this_muc_exist', true);
        $this->user->setReturnValue('getUserName', 'mockuser1');
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectOnce('muc_remove_member', array('mockproject', 'mockuser1'));
        
        $params = array('user_id' => 4586, 'group_id' => 1);
        $this->implugin->projectRemoveUser($params);
    }
    
    function testProjectRemoveUserMucRoomDoesNotExist() {
        // same test than testProjectRemoveUser but muc room does not exists
        $this->implugin->setReturnValue('_this_muc_exist', false);
        $this->user->setReturnValue('getUserName', 'mockuser1');
        $this->implugin->expectOnce('_this_muc_exist', array('mockproject'));
        $this->jabbex->expectCallCount('muc_remove_member', 0);
        
        $params = array('user_id' => 4586, 'group_id' => 1);
        $this->implugin->projectRemoveUser($params);
    }
    
}

?>
