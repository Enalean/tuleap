<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

require_once(dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php');
require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generatePartial('Tracker', 
                      'TrackerTestVersion', 
                      array(
                          'displaySubmit',
                          'displayAdmin',
                          'displayAdminOptions',
                          'displayAdminPerms',
                          'displayAdminPermsTracker',
                          'displayAdminPermsFields',
                          'displayAdminFormElements',
                          'getTrackerSemanticManager',
                          'getNotificationsManager',
                          'getCannedResponseManager',
                          'getCannedResponseFactory',
                          'getFormElementFactory',
                          'getReportFactory',
                          'getWorkflowFactory',
                          'getWorkflowManager',
                          'getTrackerFactory',
                          'getGroupId',
                          'getPermissions',
                          'getFormELements',
                          'getId',
                          'sendXML',
                          'isUsed',
                          'getAllFormElements',
                          'getTrackerArtifactFactory',
                          'aidExists',
                          'getUserManager'
                      )
);

Mock::generatePartial('Tracker', 
                      'TrackerTestVersionForIsValid', 
                      array(
                          'displaySubmit',
                          'displayAdmin',
                          'displayAdminOptions',
                          'displayAdminPerms',
                          'displayAdminPermsTracker',
                          'displayAdminPermsFields',
                          'displayAdminFormElements',
                          'getTrackerSemanticManager',
                          'getNotificationsManager',
                          'getCannedResponseManager',
                          'getCannedResponseFactory',
                          'getFormElementFactory',
                          'getReportFactory',
                          'getWorkflowFactory',
                          'getWorkflowManager',
                          'getTrackerFactory',
                          'getGroupId',
                          'getPermissions',
                          'getFormELements',
                          'getId',
                          'sendXML',
                          'isUsed',
                          'getAllFormElements',
                          'getTrackerArtifactFactory',
                          'aidExists',
                          'getUserManager',
                          'hasError'
                      )
);

Mock::generatePartial('Tracker', 
                      'TrackerTestVersionForAccessPerms', 
                      array(
                          'getGroupId',
                          'getPermissions',
                          'getId',
                          'getUserManager'
                      )
);

require_once('common/include/Codendi_Request.class.php');
Mock::generate('Codendi_Request');

require_once('common/user/User.class.php');
Mock::generate('User');

require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');

require_once(dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php');
Mock::generate('TrackerManager');

require_once(dirname(__FILE__).'/../include/Tracker/TrackerFactory.class.php');
Mock::generate('TrackerFactory');

require_once(dirname(__FILE__).'/../include/Tracker/Semantic/Tracker_SemanticManager.class.php');
Mock::generate('Tracker_SemanticManager');

require_once(dirname(__FILE__).'/../include/Tracker/Tracker_NotificationsManager.class.php');
Mock::generate('Tracker_NotificationsManager');

require_once(dirname(__FILE__).'/../include/Tracker/CannedResponse/Tracker_CannedResponseManager.class.php');
Mock::generate('Tracker_CannedResponseManager');

require_once(dirname(__FILE__).'/../include/workflow/WorkflowManager.class.php');
Mock::generate('WorkflowManager');

require_once(dirname(__FILE__).'/../include/Tracker/Report/Tracker_ReportFactory.class.php');
Mock::generate('Tracker_ReportFactory');

require_once(dirname(__FILE__).'/../include/workflow/WorkflowFactory.class.php');
Mock::generate('WorkflowFactory');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElementFactory.class.php');
Mock::generate('Tracker_FormElementFactory');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_String.class.php');
Mock::generate('Tracker_FormElement_Field_String');

require_once(dirname(__FILE__).'/../include/Tracker/CannedResponse/Tracker_CannedResponseFactory.class.php');
Mock::generate('Tracker_CannedResponseFactory');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Interface.class.php');
Mock::generate('Tracker_FormElement_Interface');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_ArtifactFactory.class.php');
Mock::generate('Tracker_ArtifactFactory');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');
Mock::generate('Tracker_Artifact');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_SharedFormElementFactory.class.php');
Mock::generate('Tracker_SharedFormElementFactory');

require_once 'Test_Tracker_Builder.php';

class Tracker_FormElement_InterfaceTestVersion extends MockTracker_FormElement_Interface {
    public function exportToXML($root, &$xmlMapping, &$index) {
        $xmlMapping['F'. $index] = $this->getId();
        return parent::exportToXML($root, $xmlMapping, $index);
    }
}

require_once('common/layout/Layout.class.php');
Mock::generate('Layout');

class TrackerTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->tracker = new TrackerTestVersion();
        $this->tracker1 = new TrackerTestVersion();
        $this->tracker2 = new TrackerTestVersion();
        $this->tracker_manager = new MockTrackerManager();
        
        $this->tf = new MockTrackerFactory();
        $this->tracker->setReturnReference('getTrackerFactory', $this->tf);
        $this->tracker1->setReturnReference('getTrackerFactory', $this->tf);
        $this->tracker2->setReturnReference('getTrackerFactory', $this->tf);
        $this->tsm = new MockTracker_SemanticManager();
        $this->tracker->setReturnReference('getTrackerSemanticManager', $this->tsm);
        $this->tracker1->setReturnReference('getTrackerSemanticManager', $this->tsm);
        $this->tracker2->setReturnReference('getTrackerSemanticManager', $this->tsm);
        $this->tnm = new MockTracker_NotificationsManager();
        $this->tracker->setReturnReference('getNotificationsManager', $this->tnm);
        $this->tracker1->setReturnReference('getNotificationsManager', $this->tnm);
        $this->tracker2->setReturnReference('getNotificationsManager', $this->tnm);
        $this->tcrm = new MockTracker_CannedResponseManager();
        $this->tracker->setReturnReference('getCannedResponseManager', $this->tcrm);
        $this->tracker1->setReturnReference('getCannedResponseManager', $this->tcrm);
        $this->tracker2->setReturnReference('getCannedResponseManager', $this->tcrm);
        $this->wm = new MockWorkflowManager();
        $this->tracker->setReturnReference('getWorkflowManager', $this->wm);
        $this->tracker1->setReturnReference('getWorkflowManager', $this->wm);
        $this->tracker2->setReturnReference('getWorkflowManager', $this->wm);
        $group_id = 999;
        $this->tracker->setReturnValue('getGroupId', $group_id);
        $this->tracker->setReturnValue('getId', 110);
        $this->tracker1->setReturnValue('getGroupId', $group_id);
        $this->tracker1->setReturnValue('getId', 111);
        $this->tracker2->setReturnValue('getGroupId', $group_id);
        $this->tracker2->setReturnValue('getId', 112);
        
        
        $this->tracker->setReturnValue('getPermissions', array(
            1 => array('PERM_1'),
            3 => array('PERM_2'),
            5 => array('PERM_3'),
            115 => array('PERM_3'),
        ));
        $this->tracker1->setReturnValue('getPermissions', array(
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        ));
        $this->tracker2->setReturnValue('getPermissions', array(
            1002 => array( 102 => 'PLUGIN_TRACKER_ADMIN'),
        ));
        
        $this->site_admin_user = new MockUser($this);
        $this->site_admin_user->setReturnValue('getId', 1);
        $this->site_admin_user->setReturnValue('isMember', false);
        $this->site_admin_user->setReturnValue('isSuperUser', true);
        $this->site_admin_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->site_admin_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->site_admin_user->setReturnValue('isLoggedIn', true);
        
        $this->project_admin_user = new MockUser($this);
        $this->project_admin_user->setReturnValue('getId', 123);
        $this->project_admin_user->setReturnValue('isMember', true, array($group_id, 'A'));
        $this->project_admin_user->setReturnValue('isSuperUser', false);
        $this->project_admin_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->project_admin_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->project_admin_user->setReturnValue('isLoggedIn', true);
        
        $this->all_trackers_admin_user = new MockUser($this);
        $this->all_trackers_admin_user->setReturnValue('getId', 222);
        $this->all_trackers_admin_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->all_trackers_admin_user->setReturnValue('isSuperUser', false);
        $this->all_trackers_admin_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->all_trackers_admin_user->setReturnValue('isMemberOfUGroup', true, array(1001, '*')); //1001 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->setReturnValue('isMemberOfUGroup', true, array(1002, '*')); //1002 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->setReturnValue('isLoggedIn', true);
                
        $this->tracker1_admin_user = new MockUser($this);
        $this->tracker1_admin_user->setReturnValue('getId', 333);
        $this->tracker1_admin_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->tracker1_admin_user->setReturnValue('isSuperUser', false);
        $this->tracker1_admin_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->tracker1_admin_user->setReturnValue('isMemberOfUGroup', true, array(1001, '*'));
        $this->tracker1_admin_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->tracker1_admin_user->setReturnValue('isLoggedIn', true);
        
        $this->tracker2_admin_user = new MockUser($this);
        $this->tracker2_admin_user->setReturnValue('getId', 444);
        $this->tracker2_admin_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->tracker2_admin_user->setReturnValue('isSuperUser', false);
        $this->tracker2_admin_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->tracker2_admin_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->tracker2_admin_user->setReturnValue('isMemberOfUGroup', true, array(1002, '*'));
        $this->tracker2_admin_user->setReturnValue('isLoggedIn', true);
        
        $this->project_member_user = new MockUser($this);
        $this->project_member_user->setReturnValue('getId', 555);
        $this->project_member_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->project_member_user->setReturnValue('isSuperUser', false);
        $this->project_member_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->project_member_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->project_member_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->project_member_user->setReturnValue('isTrackerAdmin', false);
        $this->project_member_user->setReturnValue('isLoggedIn', true);
        
        $this->registered_user = new MockUser($this);
        $this->registered_user->setReturnValue('getId', 777);
        $this->registered_user->setReturnValue('isMember', false);
        $this->registered_user->setReturnValue('isSuperUser', false);
        $this->registered_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->registered_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->registered_user->setReturnValue('isLoggedIn', true);
        
        $this->anonymous_user = new MockUser($this);
        $this->anonymous_user->setReturnValue('getId', 777);
        $this->anonymous_user->setReturnValue('isMember', false);
        $this->anonymous_user->setReturnValue('isSuperUser', false);
        $this->anonymous_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->anonymous_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->anonymous_user->setReturnValue('isLoggedIn', false);
        
        // Users for tracker access perm tests
        $this->anonymous = new MockUser();
        $this->anonymous->setReturnValue('isSuperUser', false);
        $this->anonymous->setReturnValue('getId', 0);
        $this->anonymous->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(2, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(3, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(138, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(196, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->anonymous->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
         
        $this->registered = new MockUser();
        $this->registered->setReturnValue('isSuperUser',false);
        $this->registered->setReturnValue('getId', 101);
        $this->registered->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', true, array(2, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', false, array(3, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', false, array(138, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', false, array(196, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->registered->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
         
        $this->project_member = new MockUser();
        $this->project_member->setReturnValue('isSuperUser', false);
        $this->project_member->setReturnValue('getId', 102);
        $this->project_member->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', true, array(2, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', true, array(3, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', false, array(138, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', false, array(196, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->project_member->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
         
        $this->project_admin = new MockUser();
        $this->project_admin->setReturnValue('isSuperUser', false);
        $this->project_admin->setReturnValue('getId', 103);
        $this->project_admin->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', true, array(2, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', true, array(3, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', true, array(4, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', false, array(138, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', false, array(196, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->project_admin->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
         
        $this->super_admin = new MockUser();
        $this->super_admin->setReturnValue('isSuperUser', true);
        $this->super_admin->setReturnValue('getId', 104);
        $this->super_admin->setReturnValue('isMemberOfUGroup', true, array('*', '*'));
        $this->super_admin->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->super_admin->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
         
        $this->tracker_submitter = new MockUser();
        $this->tracker_submitter->setReturnValue('isSuperUser', false);
        $this->tracker_submitter->setReturnValue('getId', 105);
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', false, array(2, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', false, array(3, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', true, array(138, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', false, array(196, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->tracker_submitter->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
         
        $this->tracker_assignee = new MockUser();
        $this->tracker_assignee->setReturnValue('isSuperUser', false);
        $this->tracker_assignee->setReturnValue('getId', 106);
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', false, array(2, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', false, array(3, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', false, array(138, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', true, array(196, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->tracker_assignee->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        
        $this->tracker_submitterassignee = new MockUser();
        $this->tracker_submitterassignee->setReturnValue('isSuperUser', false);
        $this->tracker_submitterassignee->setReturnValue('getId', 107);
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', true, array(1, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', false, array(2, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', false, array(3, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', true, array(138, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', true, array(196, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->tracker_submitterassignee->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        
        $this->tracker_admin = new MockUser();
        $this->tracker_admin->setReturnValue('isSuperUser', false);
        $this->tracker_admin->setReturnValue('getId', 107);
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(1, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(2, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(3, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(4, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(138, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(196, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', true, array(1001, '*'));
        $this->tracker_admin->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        
        $this->workflow_factory = new MockWorkflowFactory();
        $this->tracker->setReturnReference('getWorkflowFactory', $this->workflow_factory);

        $this->formelement_factory = new MockTracker_FormElementFactory();
        $this->tracker->setReturnReference('getFormElementFactory', $this->formelement_factory);

        $this->report_factory = new MockTracker_ReportFactory();
        $this->tracker->setReturnReference('getReportFactory', $this->report_factory);

        $this->canned_response_factory = new MockTracker_CannedResponseFactory();
        $this->tracker->setReturnReference('getCannedResponseFactory', $this->canned_response_factory);

        $GLOBALS['Response'] = new MockLayout();

        $GLOBALS['UGROUPS'] = array(
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        );
    }
    public function tearDown() {
        unset($this->site_admin_user);
        unset($this->project_admin_user);
        unset($this->all_trackers_admin_user);
        unset($this->tracker1_admin_user);
        unset($this->tracker2_admin_user);
        unset($this->project_member_user);
        unset($this->registered_user);
        unset($this->anonymous);
        unset($this->registered);
        unset($this->project_member);
        unset($this->project_admin);
        unset($this->super_admin);
        unset($this->tracker_submitter);
        unset($this->tracker_assignee);
        unset($this->tracker_submitterassignee);
        unset($this->tracker_admin);
        parent::tearDown();
    }
    
    //
    // New artifact permissions
    //
    public function testPermsNewArtifactSiteAdmin() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));
        
        // site admin can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }
    public function testPermsNewArtifactProjectAdmin() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));
        
        // project admin can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->project_admin_user);
    }
    public function testPermsNewArtifactTrackerAdmin() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));
        
        // tracker admin can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->all_trackers_admin_user);
    }
    public function testPermsNewArtifactProjectMember() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));
        
        // project member can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->project_member_user);
    }
    public function testPermsNewArtifactRegisteredUser() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));
        
        // registered user can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->registered_user);
    }
    
    //
    // Delete tracker permissions
    //
    public function testPermsDeleteTrackerSiteAdmin() {
        $request_delete_tracker = new MockCodendi_Request($this);
        $request_delete_tracker->setReturnValue('get', 'delete', array('func'));
        
        // site admin can delete trackers
        $this->tracker->expectOnce('getTrackerFactory');
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->site_admin_user);
    }
    public function testPermsDeleteTrackerProjectAdmin() {
        $request_delete_tracker = new MockCodendi_Request($this);
        $request_delete_tracker->setReturnValue('get', 'delete', array('func'));
        
        // project admin can delete trackers
        $this->tracker->expectOnce('getTrackerFactory');
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->project_admin_user);
    }
    public function testPermsDeleteTrackerTrackerAdmin() {
        $request_delete_tracker = new MockCodendi_Request($this);
        $request_delete_tracker->setReturnValue('get', 'delete', array('func'));
        
        // tracker admin can NOT delete trackers if he's not project admin
        $this->tracker->expectNever('getTrackerFactory');
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsDeleteTrackerProjectMember() {
        $request_delete_tracker = new MockCodendi_Request($this);
        $request_delete_tracker->setReturnValue('get', 'delete', array('func'));
        
        // project member can NOT delete tracker
        $this->tracker->expectNever('getTrackerFactory');
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->project_member_user);
    }
    public function testPermsDeleteTrackerRegisteredUser() {
        $request_delete_tracker = new MockCodendi_Request($this);
        $request_delete_tracker->setReturnValue('get', 'delete', array('func'));
        
        // registered user can NOT delete trackers
        $this->tracker->expectNever('getTrackerFactory');
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->registered_user);
    }
    
    //
    // Tracker admin permissions
    //
    public function testPermsAdminTrackerSiteAdmin() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('displayAdmin');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->site_admin_user);
    }
    public function testPermsAdminTrackerProjectAdmin() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('displayAdmin');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_admin_user);
    }
    public function testPermsAdminTrackerTrackerAdmin() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdmin');
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('displayAdmin');
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminTrackerTracker1Admin() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdmin');
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('displayAdmin');
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminTrackerTracker2Admin() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('displayAdmin');
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('displayAdmin');
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminTrackerProjectMember() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('displayAdmin');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_member_user);
    }
    public function testPermsAdminTrackerRegisteredUser() {
        $request_admin_tracker = new MockCodendi_Request($this);
        $request_admin_tracker->setReturnValue('get', 'admin', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('displayAdmin');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->registered_user);
    }
    
    //
    // Tracker admin edit option permissions
    //
    public function testPermsAdminEditOptionsTrackerSiteAdmin() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->site_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerProjectAdmin() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerTrackerAdmin() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminOptions');
        $this->tracker1->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('displayAdminOptions');
        $this->tracker2->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerTracker1Admin() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminOptions');
        $this->tracker1->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('displayAdminOptions');
        $this->tracker2->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerTracker2Admin() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('displayAdminOptions');
        $this->tracker1->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('displayAdminOptions');
        $this->tracker2->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerProjectMember() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_member_user);
    }
    public function testPermsAdminEditOptionsTrackerRegisteredUser() {
        $request_admin_editoptions_tracker = new MockCodendi_Request($this);
        $request_admin_editoptions_tracker->setReturnValue('get', 'admin-editoptions', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin perms" permissions
    //
    public function testPermsAdminPermsTrackerSiteAdmin() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminPerms');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->site_admin_user);
    }
    public function testPermsAdminPermsTrackerProjectAdmin() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminPerms');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerAdmin() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminPerms');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('displayAdminPerms');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminPermsTrackerTracker1Admin() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminPerms');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('displayAdminPerms');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminPermsTrackerTracker2Admin() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('displayAdminPerms');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('displayAdminPerms');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminPermsTrackerProjectMember() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminPerms');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_member_user);
    }
    public function testPermsAdminPermsTrackerRegisteredUser() {
        $request_admin_perms_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker->setReturnValue('get', 'admin-perms', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminPerms');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin perms tracker" permissions
    //
    public function testPermsAdminPermsTrackerTrackerSiteAdmin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminPermsTracker');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->site_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerProjectAdmin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminPermsTracker');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->project_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTrackerAdmin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminPermsTracker');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('displayAdminPermsTracker');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTracker1Admin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminPermsTracker');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('displayAdminPermsTracker');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTracker2Admin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('displayAdminPermsTracker');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('displayAdminPermsTracker');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerProjectMember() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminPermsTracker');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->project_member_user);
    }
    public function testPermsAdminPermsTrackerTrackerRegisteredUser() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminPermsTracker');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin perms fields" permissions
    //
    public function testPermsAdminPermsFieldsTrackerSiteAdmin() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminPermsFields');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->site_admin_user);
    }
    public function testPermsAdminPermsFieldsTrackerProjectAdmin() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminPermsFields');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->project_admin_user);
    }
    public function testPermsAdminPermsFieldsTrackerTrackerAdmin() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminPermsFields');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('displayAdminPermsFields');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminPermsFieldsTrackerTracker1Admin() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminPermsFields');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('displayAdminPermsFields');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminPermsFieldsTrackerTracker2Admin() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('displayAdminPermsFields');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('displayAdminPermsFields');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminPermsFieldsTrackerProjectMember() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminPermsFields');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->project_member_user);
    }
    public function testPermsAdminPermsFieldsTrackerRegisteredUser() {
        $request_admin_perms_fields_tracker = new MockCodendi_Request($this);
        $request_admin_perms_fields_tracker->setReturnValue('get', 'admin-perms-fields', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminPermsFields');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_fields_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin form elements" permissions
    //
    public function testPermsAdminFormElementTrackerSiteAdmin() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->site_admin_user);
    }
    public function testPermsAdminFormElementTrackerProjectAdmin() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_admin_user);
    }
    public function testPermsAdminFormElementTrackerTrackerAdmin() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminFormElements');
        $this->tracker1->process($this->tracker_manager, $request_admin_formelement_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('displayAdminFormElements');
        $this->tracker2->process($this->tracker_manager, $request_admin_formelement_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminFormElementTrackerTracker1Admin() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('displayAdminFormElements');
        $this->tracker1->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('displayAdminFormElements');
        $this->tracker2->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminFormElementTrackerTracker2Admin() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('displayAdminFormElements');
        $this->tracker1->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('displayAdminFormElements');
        $this->tracker2->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminFormElementTrackerProjectMember() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_member_user);
    }
    public function testPermsAdminFormElementTrackerRegisteredUser() {
        $request_admin_formelement_tracker = new MockCodendi_Request($this);
        $request_admin_formelement_tracker->setReturnValue('get', 'admin-formElements', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin semantic" permissions
    //
    public function testPermsAdminSemanticTrackerSiteAdmin() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('getTrackerSemanticManager');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->site_admin_user);
    }
    public function testPermsAdminSemanticTrackerProjectAdmin() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('getTrackerSemanticManager');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_admin_user);
    }
    public function testPermsAdminSemanticTrackerTrackerAdmin() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getTrackerSemanticManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('getTrackerSemanticManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminSemanticTrackerTracker1Admin() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getTrackerSemanticManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('getTrackerSemanticManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminSemanticTrackerTracker2Admin() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('getTrackerSemanticManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('getTrackerSemanticManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminSemanticTrackerProjectMember() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('getTrackerSemanticManager');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_member_user);
    }
    public function testPermsAdminSemanticTrackerRegisteredUser() {
        $request_admin_semantic_tracker = new MockCodendi_Request($this);
        $request_admin_semantic_tracker->setReturnValue('get', 'admin-semantic', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('getTrackerSemanticManager');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin notification" permissions
    //
    public function testPermsAdminNotificationTrackerSiteAdmin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // site admin can access tracker notification admin part
        $this->tracker->expectOnce('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->site_admin_user);
    }
    public function testPermsAdminNotificationTrackerProjectAdmin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // project admin can access tracker notification admin part
        $this->tracker->expectOnce('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->project_admin_user);
    }
    public function testPermsAdminNotificationTrackerTrackerAdmin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // tracker admin can access tracker notification admin part
        $this->tracker1->expectOnce('getNotificationsManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_notification_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('getNotificationsManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_notification_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminNotificationTrackerTracker1Admin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // tracker admin can access tracker notification admin part
        $this->tracker1->expectOnce('getNotificationsManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('getNotificationsManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminNotificationTrackerTracker2Admin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // tracker admin can access tracker notification admin part
        $this->tracker1->expectNever('getNotificationsManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('getNotificationsManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminNotificationTrackerProjectMember() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // project member can't access tracker notification admin part
        $this->tracker->expectNever('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->project_member_user);
    }
    public function testPermsAdminNotificationTrackerRegisteredUser() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // registered user can't access tracker notification admin part
        $this->tracker->expectNever('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->registered_user);
    }
    public function testPermsAdminNotificationTrackerAnonymousUser() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'admin-notifications', array('func'));
        
        // anonymous user can't access tracker notification admin part
        $this->tracker->expectNever('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->anonymous_user);
    }
    
    //
    // Tracker "notification" permissions (not admin !)
    //
    public function testPermsNotificationTrackerSiteAdmin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // site admin can access tracker notification user part
        $this->tracker->expectOnce('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->site_admin_user);
    }
    public function testPermsNotificationTrackerProjectAdmin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // project admin can access tracker notification user part
        $this->tracker->expectOnce('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->project_admin_user);
    }
    public function testPermsNotificationTrackerTrackerAdmin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // tracker admin can access tracker notification user part
        $this->tracker1->expectOnce('getNotificationsManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_notification_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('getNotificationsManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_notification_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsNotificationTrackerTracker1Admin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // tracker admin can access tracker notification user part
        $this->tracker1->expectOnce('getNotificationsManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectOnce('getNotificationsManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker1_admin_user);
    }
    public function testPermsNotificationTrackerTracker2Admin() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // tracker admin can access tracker notification user part
        $this->tracker1->expectOnce('getNotificationsManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('getNotificationsManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_notification_tracker, $this->tracker2_admin_user);
    }
    public function testPermsNotificationTrackerProjectMember() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // project member can access tracker notification user part
        $this->tracker->expectOnce('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->project_member_user);
    }
    public function testPermsNotificationTrackerRegisteredUser() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // registered user can access tracker notification user part
        $this->tracker->expectOnce('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->registered_user);
    }
    public function testPermsNotificationTrackerAnonymousUser() {
        $request_admin_notification_tracker = new MockCodendi_Request($this);
        $request_admin_notification_tracker->setReturnValue('get', 'notifications', array('func'));
        
        // anonymous user can access tracker notification user part
        $this->tracker->expectNever('getNotificationsManager');
        $this->tracker->process($this->tracker_manager, $request_admin_notification_tracker, $this->anonymous_user);
    }
    
    //
    // Tracker "admin canned" permissions
    //
    public function testPermsAdminCannedTrackerSiteAdmin() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('getCannedResponseManager');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->site_admin_user);
    }
    public function testPermsAdminCannedTrackerProjectAdmin() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('getCannedResponseManager');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_admin_user);
    }
    public function testPermsAdminCannedTrackerTrackerAdmin() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getCannedResponseManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('getCannedResponseManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminCannedTrackerTracker1Admin() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getCannedResponseManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('getCannedResponseManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminCannedTrackerTracker2Admin() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('getCannedResponseManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('getCannedResponseManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminCannedTrackerProjectMember() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('getCannedResponseManager');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_member_user);
    }
    public function testPermsAdminCannedTrackerRegisteredUser() {
        $request_admin_canned_tracker = new MockCodendi_Request($this);
        $request_admin_canned_tracker->setReturnValue('get', 'admin-canned', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('getCannedResponseManager');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->registered_user);
    }
    
    //
    // Tracker "admin workflow" permissions
    //
    public function testPermsAdminWorkflowTrackerSiteAdmin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // site admin can access tracker admin part
        $this->tracker->expectOnce('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->site_admin_user);
    }
    public function testPermsAdminWorkflowTrackerProjectAdmin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // project admin can access tracker admin part
        $this->tracker->expectOnce('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTrackerAdmin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getWorkflowManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('getWorkflowManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTracker1Admin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getWorkflowManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('getWorkflowManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTracker2Admin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('getWorkflowManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('getWorkflowManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminWorkflowTrackerProjectMember() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // project member can NOT access tracker admin part
        $this->tracker->expectNever('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_member_user);
    }
    public function testPermsAdminWorkflowTrackerRegisteredUser() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', 'admin-workflow', array('func'));
        
        // registered user can NOT access tracker admin part
        $this->tracker->expectNever('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->registered_user);
    }
    
    //
    // Tracker "access" permissions
    //
    public function testAccessPermsAnonymousFullAccess() {
        $t_access_anonymous = new TrackerTestVersionForAccessPerms();
        $t_access_anonymous->setReturnValue('getId', 1);
        $t_access_anonymous->setReturnValue('getGroupId', 101);
        $perms = array(
                1 => array( 101 => 'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_anonymous->setReturnReference('getPermissions', $perms);
        
        $this->assertTrue($t_access_anonymous->userCanView($this->anonymous));
        $this->assertTrue($t_access_anonymous->userCanView($this->registered));
        $this->assertTrue($t_access_anonymous->userCanView($this->project_member));
        $this->assertTrue($t_access_anonymous->userCanView($this->project_admin));
        $this->assertTrue($t_access_anonymous->userCanView($this->super_admin));
        $this->assertTrue($t_access_anonymous->userCanView($this->tracker_submitter));
        $this->assertTrue($t_access_anonymous->userCanView($this->tracker_assignee));
        $this->assertTrue($t_access_anonymous->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_anonymous->userCanView($this->tracker_admin));
    }
    
    public function testAccessPermsRegisteredFullAccess() {
        $t_access_registered = new TrackerTestVersion();
        $t_access_registered->setReturnValue('getId', 2);
        $t_access_registered->setReturnValue('getGroupId', 101);
        $perms = array(
                2 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_registered->setReturnReference('getPermissions', $perms);
        
        $this->assertFalse($t_access_registered->userCanView($this->anonymous));
        $this->assertTrue($t_access_registered->userCanView($this->registered));
        $this->assertTrue($t_access_registered->userCanView($this->project_member));
        $this->assertTrue($t_access_registered->userCanView($this->project_admin));
        $this->assertTrue($t_access_registered->userCanView($this->super_admin));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_submitter));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_assignee));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_registered->userCanView($this->tracker_admin));
    }
    
    public function testAccessPermsMemberFullAccess() {
        $t_access_members = new TrackerTestVersion();
        $t_access_members->setReturnValue('getId', 3);
        $t_access_members->setReturnValue('getGroupId', 101);
        $perms = array(
                3 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_members->setReturnReference('getPermissions', $perms);
        
        $this->assertFalse($t_access_members->userCanView($this->anonymous));
        $this->assertFalse($t_access_members->userCanView($this->registered));
        $this->assertTrue($t_access_members->userCanView($this->project_member));
        $this->assertTrue($t_access_members->userCanView($this->project_admin));
        $this->assertTrue($t_access_members->userCanView($this->super_admin));
        $this->assertFalse($t_access_members->userCanView($this->tracker_submitter));
        $this->assertFalse($t_access_members->userCanView($this->tracker_assignee));
        $this->assertFalse($t_access_members->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_members->userCanView($this->tracker_admin));
    }
    
    public function testAccessPermsAdminFullAccess() {
        $t_access_admin = new TrackerTestVersion();
        $t_access_admin->setReturnValue('getId', 4);
        $t_access_admin->setReturnValue('getGroupId', 101);
        $perms = array(
                4 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
                );
        $t_access_admin->setReturnReference('getPermissions', $perms);
        
        $this->assertFalse($t_access_admin->userCanView($this->anonymous));
        $this->assertFalse($t_access_admin->userCanView($this->registered));
        $this->assertFalse($t_access_admin->userCanView($this->project_member));
        $this->assertTrue($t_access_admin->userCanView($this->project_admin));
        $this->assertTrue($t_access_admin->userCanView($this->super_admin));
        $this->assertFalse($t_access_admin->userCanView($this->tracker_submitter));
        $this->assertFalse($t_access_admin->userCanView($this->tracker_assignee));
        $this->assertFalse($t_access_admin->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_admin->userCanView($this->tracker_admin));
    }
    
    public function testAccessPermsSubmitterFullAccess() {
        $t_access_submitter = new TrackerTestVersion();
        $t_access_submitter->setReturnValue('getId', 5);
        $t_access_submitter->setReturnValue('getGroupId', 101);
        $perms = array(
                4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                138 => array(101=>'PLUGIN_TRACKER_ACCESS_SUBMITTER'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_submitter->setReturnReference('getPermissions', $perms);
        
        $this->assertFalse($t_access_submitter->userCanView($this->anonymous));
        $this->assertFalse($t_access_submitter->userCanView($this->registered));
        $this->assertFalse($t_access_submitter->userCanView($this->project_member));
        $this->assertTrue($t_access_submitter->userCanView($this->project_admin));
        $this->assertTrue($t_access_submitter->userCanView($this->super_admin));
        $this->assertTrue($t_access_submitter->userCanView($this->tracker_submitter));
        $this->assertFalse($t_access_submitter->userCanView($this->tracker_assignee));
        $this->assertTrue($t_access_submitter->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_submitter->userCanView($this->tracker_admin));
    }
    
    public function testAccessPermsAssigneeFullAccess() {
        $t_access_assignee = new TrackerTestVersion();
        $t_access_assignee->setReturnValue('getId', 6);
        $t_access_assignee->setReturnValue('getGroupId', 101);
        $perms = array(
                4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                196 => array(101=>'PLUGIN_TRACKER_ACCESS_ASSIGNEE'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_assignee->setReturnReference('getPermissions', $perms);
        
        $this->assertFalse($t_access_assignee->userCanView($this->anonymous));
        $this->assertFalse($t_access_assignee->userCanView($this->registered));
        $this->assertFalse($t_access_assignee->userCanView($this->project_member));
        $this->assertTrue($t_access_assignee->userCanView($this->project_admin));
        $this->assertTrue($t_access_assignee->userCanView($this->super_admin));
        $this->assertFalse($t_access_assignee->userCanView($this->tracker_submitter));
        $this->assertTrue($t_access_assignee->userCanView($this->tracker_assignee));
        $this->assertTrue($t_access_assignee->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_assignee->userCanView($this->tracker_admin));
    }
    
    public function testAccessPermsSubmitterAssigneeFullAccess() {
        $t_access_submitterassignee  = new TrackerTestVersion();
        $t_access_submitterassignee->setReturnValue('getId', 7);
        $t_access_submitterassignee->setReturnValue('getGroupId', 101);
        
        $perms = array(
                4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                138 => array(101=>'PLUGIN_TRACKER_ACCESS_SUBMITTER'),
                196 => array(101=>'PLUGIN_TRACKER_ACCESS_ASSIGNEE'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_submitterassignee->setReturnReference('getPermissions', $perms);
        
        $this->assertFalse($t_access_submitterassignee->userCanView($this->anonymous));
        $this->assertFalse($t_access_submitterassignee->userCanView($this->registered));
        $this->assertFalse($t_access_submitterassignee->userCanView($this->project_member));
        $this->assertTrue($t_access_submitterassignee->userCanView($this->project_admin));
        $this->assertTrue($t_access_submitterassignee->userCanView($this->super_admin));
        $this->assertTrue($t_access_submitterassignee->userCanView($this->tracker_submitter));
        $this->assertTrue($t_access_submitterassignee->userCanView($this->tracker_assignee));
        $this->assertTrue($t_access_submitterassignee->userCanView($this->tracker_submitterassignee));
        $this->assertTrue($t_access_submitterassignee->userCanView($this->tracker_admin));
    }
    
    //
    // Tracker permission export
    //
    public function testPermissionsExport() {
        $f1 = new Tracker_FormElement_InterfaceTestVersion();
        $f1->setReturnValue('getId', 10);
        $f1->setReturnValue(
            'getPermissions', 
            array(
                2 => array('FIELDPERM_1'),
                4 => array('FIELDPERM_2'),
            )
        );
        $f1->setReturnValue('isUsed', true);

        $f2 = new Tracker_FormElement_InterfaceTestVersion();
        $f2->setReturnValue('getId', 20);
        $f2->setReturnValue(
            'getPermissions', 
            array(
                2 => array('FIELDPERM_2'),
                4 => array('FIELDPERM_1'),
            )
        );
        $f2->setReturnValue('isUsed', true);

        $this->tracker->setReturnValue('getAllFormElements', array($f1, $f2));
        $this->formelement_factory->setReturnValue('getAllFormElementsForTracker', array($f1, $f2));

        $xml = $this->tracker->exportToXML();
        
        $this->assertTrue(isset($xml->permissions));        
        $this->assertEqual((string)$xml->permissions->permission[0]['scope'], 'tracker');
        $this->assertEqual((string)$xml->permissions->permission[0]['ugroup'], 'UGROUP_1');
        $this->assertEqual((string)$xml->permissions->permission[0]['type'], 'PERM_1');

        $this->assertEqual((string)$xml->permissions->permission[1]['scope'], 'tracker');
        $this->assertEqual((string)$xml->permissions->permission[1]['ugroup'], 'UGROUP_3');
        $this->assertEqual((string)$xml->permissions->permission[1]['type'], 'PERM_2');

        $this->assertEqual((string)$xml->permissions->permission[2]['scope'], 'tracker');
        $this->assertEqual((string)$xml->permissions->permission[2]['ugroup'], 'UGROUP_5');
        $this->assertEqual((string)$xml->permissions->permission[2]['type'], 'PERM_3');
        
        $this->assertEqual((string)$xml->permissions->permission[3]['scope'], 'field');
        $this->assertEqual((string)$xml->permissions->permission[3]['ugroup'], 'UGROUP_2');
        $this->assertEqual((string)$xml->permissions->permission[3]['type'], 'FIELDPERM_1');
        $this->assertEqual((string)$xml->permissions->permission[3]['REF'], 'F1');

        $this->assertEqual((string)$xml->permissions->permission[4]['scope'], 'field');
        $this->assertEqual((string)$xml->permissions->permission[4]['ugroup'], 'UGROUP_4');
        $this->assertEqual((string)$xml->permissions->permission[4]['type'], 'FIELDPERM_2');
        $this->assertEqual((string)$xml->permissions->permission[4]['REF'], 'F1');

        $this->assertEqual((string)$xml->permissions->permission[5]['scope'], 'field');
        $this->assertEqual((string)$xml->permissions->permission[5]['ugroup'], 'UGROUP_2');
        $this->assertEqual((string)$xml->permissions->permission[5]['type'], 'FIELDPERM_2');
        $this->assertEqual((string)$xml->permissions->permission[5]['REF'], 'F2');

        $this->assertEqual((string)$xml->permissions->permission[6]['scope'], 'field');
        $this->assertEqual((string)$xml->permissions->permission[6]['ugroup'], 'UGROUP_4');
        $this->assertEqual((string)$xml->permissions->permission[6]['type'], 'FIELDPERM_1');
        $this->assertEqual((string)$xml->permissions->permission[6]['REF'], 'F2');
    }
    
    public function testHasErrorNoError() {
        $header = array('summary', 'details');
        $lines = array(
                    array('summary 1', 'details 1'),
                    array('summary 2', 'details 2'),
                 );
        $field1 = new MockTracker_FormElement_Field_String();
        $field2 = new MockTracker_FormElement_Field_String();
        $field1->setReturnValue('isRequired', false);
        $field2->setReturnValue('isRequired', false);
        
        $field1->setReturnValue('getId', 1);
        $field2->setReturnValue('getId', 2);
        
        $field1->setReturnValue('getFieldData', 'summary 1',array('summary 1') );
        $field1->setReturnValue('getFieldData', 'summary 2',array('summary 2') );
        
        $field2->setReturnValue('getFieldData', 'details 1',array('details 1') );
        $field2->setReturnValue('getFieldData', 'details 2',array('details 2') );
        
        $this->formelement_factory->setReturnReference('getUsedFieldByName', $field1, array(110, 'summary'));
        $this->formelement_factory->setReturnReference('getUsedFieldByName', $field2, array(110, 'details'));
        
        $artifact = new MockTracker_Artifact();
        
        $af = new MockTracker_ArtifactFactory();
        $this->tracker->setReturnReference('getTrackerArtifactFactory', $af);
        $this->tracker->setReturnValue('aidExists', false, array('0'));
        $artifact->setReturnValue('validateFields', true);
        
        $um = new MockUserManager();
        $u = new MockUser();
        $u->setReturnValue('getId', '107');
        $this->tracker->setReturnReference('getUserManager', $um);
        $um->setReturnReference('getCurrentUser', $u);
        
        
        $af->setReturnReference('getInstanceFromRow', $artifact);
        
        $GLOBALS['Response']->expectNever('addFeedback');
        $this->assertFalse($this->tracker->hasError($header, $lines));
    }
 
    public function testHasUnknownAidCreateMode() {
        $header = array('summary', 'details');
        $lines = array(
                    array('summary 1', 'details 1'),
                    array('summary 2', 'details 2'),
                    array('summary 3', 'details 3'),
                    array('summary 4', 'details 4'),
                 );
        
        $this->assertFalse($this->tracker->hasUnknownAid($header, $lines));
    }
    
    public function testHasUnknownAidUpdateModeNoError() {
        $header = array('aid','summary', 'details');
        $lines = array(
                    array('1','summary 1', 'details 1'),
                    array('2','summary 2', 'details 2'),
                    array('3','summary 3', 'details 3'),
                    array('4','summary 4', 'details 4'),
                 );
        
        $artifact1 = new MockTracker_Artifact();
        $artifact1->setReturnValue('getId', '1');
        $artifact2 = new MockTracker_Artifact();
        $artifact2->setReturnValue('getId', '2');
        $artifact3 = new MockTracker_Artifact();
        $artifact3->setReturnValue('getId', '3');
        $artifact4 = new MockTracker_Artifact();
        $artifact4->setReturnValue('getId', '4');
        
       
        $af = new MockTracker_ArtifactFactory();
        $this->tracker->setReturnReference('getTrackerArtifactFactory', $af);
        $af->setReturnReference('getArtifactById', $artifact1, array('1'));
        $af->setReturnReference('getArtifactById', $artifact2, array('2'));
        $af->setReturnReference('getArtifactById', $artifact3, array('3'));
        $af->setReturnReference('getArtifactById', $artifact4, array('4'));

        
        $this->tracker->setReturnValue('aidExists', true);
        $this->assertFalse($this->tracker->hasUnknownAid($header, $lines));
    }
    
    public function testHasUnknownAidUpdateModeError() {
        $header = array('aid','summary', 'details');
        $lines = array(
                    array('1','summary 1', 'details 1'),
                    array('2','summary 2', 'details 2'),
                    array('3','summary 3', 'details 3'),
                    array('4','summary 4', 'details 4'),
                 );
        
        $artifact1 = new MockTracker_Artifact();
        $artifact1->setReturnValue('getId', '1');
        $artifact2 = new MockTracker_Artifact();
        $artifact2->setReturnValue('getId', '2');
        $artifact3 = new MockTracker_Artifact();
        $artifact3->setReturnValue('getId', '3');
        
        $af = new MockTracker_ArtifactFactory();
        $this->tracker->setReturnReference('getTrackerArtifactFactory', $af);
        $af->setReturnReference('getArtifactById', $artifact1, array('1'));
        $af->setReturnReference('getArtifactById', $artifact2, array('2'));
        $af->setReturnReference('getArtifactById', $artifact3, array('3'));
        $af->setReturnValue('getArtifactById', null, array('4'));
        
        $this->tracker->setReturnValue('aidExists', true, array('1'));
        $this->tracker->setReturnValue('aidExists', true, array('2'));
        $this->tracker->setReturnValue('aidExists', true, array('3'));
        $this->tracker->setReturnValue('aidExists', false, array('4'));
            
        $this->assertTrue($this->tracker->hasUnknownAid($header, $lines));
    }
    
    public function testIsValidCSVWrongSeparator() {
        $lines = array(
                    array('aid;summary;details'),
                    array('1;summary 1;details 1'),
                    array('2;summary 2;details 2'),
                    array('3;summary 3;details 3'),
                    array('4;summary 4;details 4'),
                 );
        $separator = ',';
        
        $tracker = new TrackerTestVersionForIsValid();
        $tracker->setReturnValue('hasError', false);
        
        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', '*', '*'));    // expected warning about wrong separator
        $tracker->isValidCSV($lines, $separator);
    }
    
    public function testIsValidCSVGoodSeparator() {
        $lines = array(
                    array('aid', 'summary', 'details'),
                    array('1', 'summary 1', 'details 1'),
                    array('2', 'summary 2', 'details 2'),
                    array('3', 'summary 3', 'details 3'),
                    array('4', 'summary 4', 'details 4'),
                 );
        $separator = ',';
        
        $tracker = new TrackerTestVersionForIsValid();
        $tracker->setReturnValue('hasError', false);
        
        $GLOBALS['Response']->expectNever('addFeedback', array('warning', '*', '*'));
        $tracker->isValidCSV($lines, $separator);
    }
    
    public function testCreateFormElementDispatchesToOrdinaryFieldCreation() {
        $data = array('type' => 'string');
        
        list($tracker, $factory, $sharedFactory, $user) = $this->GivenATrackerAndItsFactories();
        $factory->expectOnce('createFormElement', array($tracker , $data['type'], $data));
        $sharedFactory->expectNever('createFormElement');
        
        $tracker->createFormElement($data['type'], $data, $user);
    }
    
    public function testCreateFormElementDispatchesToSharedField() {
        $data = array('type' => 'shared');
        
        list($tracker, $factory, $sharedFactory, $user) = $this->GivenATrackerAndItsFactories();
        $factory->expectNever('createFormElement');
        $sharedFactory->expectOnce('createFormElement', array($tracker , $data, $user));
        
        $tracker->createFormElement($data['type'], $data, $user);
    }
    
    private function GivenATrackerAndItsFactories() {
        $tracker = new Tracker(null, null, null, null, null, null, null, null, null, null, null, null);
        $factory = new MockTracker_FormElementFactory();
        $tracker->setFormElementFactory($factory);
        $sharedFactory = new MockTracker_SharedFormElementFactory();
        $tracker->setSharedFormElementFactory($sharedFactory);
        $user = new MockUser();
        return array($tracker, $factory, $sharedFactory, $user);
    }
}

class Tracker_ArtifactSubmit_RedirectUrlTest extends TuleapTestCase {
    public function itRedirectsToTheTrackerHomePageByDefault() {
        $request_data = array();
        $tracker_id = 20;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, null);
        $this->assertEqual(TRACKER_BASE_URL."/?tracker=$tracker_id", $redirect_uri);
    }
    
    public function itStaysOnTheCurrentArtifactWhen_submitAndStay_isSpecified() {
        $request_data = array('submit_and_stay' => true);
        $artifact_id = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$artifact_id", $redirect_uri);
    }
    
    public function itRedirectsToNewArtifactCreationWhen_submitAndContinue_isSpecified() {
        $request_data = array('submit_and_continue' => true);
        $tracker_id = 73;
        $artifact_id = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertStringBeginsWith($redirect_uri, TRACKER_BASE_URL);
        $this->assertUriHasArgument($redirect_uri, 'func', 'new-artifact');
        $this->assertUriHasArgument($redirect_uri, 'tracker', $tracker_id);
    }
    
//    public function itReturnsToThePreviousArtifactWhen_fromAid_isGiven() {
//        $from_aid = 33;
//        $request_data = array('from_aid' => $from_aid);
//        $artifact_id = 66;
//        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
//        $this->assertEqual(TRACKER_BASE_URL."/?aid=$from_aid", $redirect_uri);
//    }
//    
    public function itUsesThe_returnToUri_whenPresent() {
        $return_to = "/plugins/some_plugin/?some_arg=some_value";
        $request_data = array('return_to' => urlencode($return_to));
        $tracker_id = 73;
        $artifact_id = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertEqual($return_to, $redirect_uri);
    }

//    public function testSubmitAndStayHasPrecedenceOver_fromAid() {
//        $from_aid = 33;
//        $artifact_id = 66;
//        $request_data = array('from_aid' => $from_aid,
//                              'submit_and_stay' => true);
//        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
//        $this->assertUriHasArgument($redirect_uri, "aid", $artifact_id);
//        $this->assertUriHasArgument($redirect_uri, "from_aid", $from_aid);
//    }
//
    public function testSubmitAndStayHasPrecedenceOver_returnTo() {
        $encoded_return_uri = urlencode("/plugins/some_plugin/?some_arg=some_value");
        $request_data = array('return_to' => $encoded_return_uri,
                              'submit_and_stay' => true);
        $tracker_id = 73;
        $artifact_id = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertUriHasArgument($redirect_uri, "return_to", $encoded_return_uri);
        $this->assertUriHasArgument($redirect_uri, "aid", $artifact_id);
    }

    public function testSubmitAndContinueHasPrecedenceOver_returnTo() {
        $encoded_return_uri = urlencode("/plugins/some_plugin/?some_arg=some_value");
        $request_data = array('return_to' => $encoded_return_uri,
                              'submit_and_continue' => true);
        $tracker_id = 73;
        $artifact_id = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertUriHasArgument($redirect_uri, "return_to", $encoded_return_uri);
        $this->assertUriHasArgument($redirect_uri, "func", 'new-artifact');
    }
    
    private function getRedirectUrlFor($request_data, $tracker_id, $artifact_id) {
        $request = new Codendi_Request($request_data);
        $tracker = aTracker()->withId($tracker_id)->build();
        return $tracker->redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id);
        
    }


}



?>
