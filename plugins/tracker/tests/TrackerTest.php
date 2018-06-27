<?php
/**
 * Copyright (c) Enalean SAS, 2011 - 2018. All rights reserved.
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
require_once('bootstrap.php');
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
                          'getDateReminderManager',
                          'getCannedResponseManager',
                          'getCannedResponseFactory',
                          'getFormElementFactory',
                          'getReportFactory',
                          'getWorkflowFactory',
                          'getWorkflowManager',
                          'getTrackerManager',
                          'getTrackerFactory',
                          'getProject',
                          'getGroupId',
                          'getPermissionsByUgroupId',
                          'getFormELements',
                          'getId',
                          'getColor',
                          'sendXML',
                          'isUsed',
                          'getAllFormElements',
                          'getTrackerArtifactFactory',
                          'aidExists',
                          'getUserManager',
                          'getHierarchyFactory',
                          'getPermissionController',
                          'userCanView',
                          'getProjectUgroups',
                          'getWebhookXMLExporter'
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
                          'getDateReminderManager',
                          'getCannedResponseManager',
                          'getCannedResponseFactory',
                          'getFormElementFactory',
                          'getReportFactory',
                          'getWorkflowFactory',
                          'getWorkflowManager',
                          'getTrackerFactory',
                          'getGroupId',
                          'getPermissionsByUgroupId',
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
                          'getPermissionsByUgroupId',
                          'getId',
                          'getUserManager',
                          'getProject',
                          'getTrackerManager'
                      )
);

require_once('common/include/Codendi_Request.class.php');
Mock::generate('Codendi_Request');

require_once('common/user/User.class.php');
Mock::generate('PFUser');

require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');

Mock::generate('TrackerManager');

Mock::generate('TrackerFactory');

Mock::generate('Tracker_SemanticManager');

Mock::generate('Tracker_NotificationsManager');

Mock::generate('Tracker_DateReminderManager');

Mock::generate('Tracker_CannedResponseManager');

Mock::generate('WorkflowManager');

Mock::generate('Tracker_ReportFactory');

Mock::generate('WorkflowFactory');

Mock::generate('Tracker_FormElementFactory');

Mock::generate('Tracker_FormElement_Field_String');

Mock::generate('Tracker_CannedResponseFactory');

Mock::generate('Tracker_FormElement_Interface');

Mock::generate('Tracker_ArtifactFactory');

Mock::generate('Tracker_Artifact');

Mock::generate('Tracker_SharedFormElementFactory');


class Tracker_FormElement_InterfaceTestVersion implements Tracker_FormElement_Interface {
    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter
    ) {
        $xmlMapping['F'. $this->getId()] = $this->getId();
    }

    public function getId()
    {
    }

    public function getTracker()
    {
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
    }

    public function getPermissionsByUgroupId()
    {
    }

    public function isUsed()
    {
    }
}

require_once('common/layout/Layout.class.php');
Mock::generate('Layout');

class TrackerTest extends TuleapTestCase {

    private $all_trackers_admin_user;

    public function setUp() {
        parent::setUp();

        $this->project = mock('Project');
        $this->project->setReturnValue('getID', 101);
        $this->project->setReturnValue('isPublic', true);

        $this->project_private = mock('Project');
        $this->project_private->setReturnValue('getID', 102);
        $this->project_private->setReturnValue('isPublic', false);

        $this->tracker = new TrackerTestVersion();
        $this->tracker1 = new TrackerTestVersion();
        $this->tracker2 = new TrackerTestVersion();
        $this->tracker_manager = new MockTrackerManager();

        $this->tracker->setReturnReference('getTrackerManager', $this->tracker_manager);
        $this->tracker1->setReturnReference('getTrackerManager', $this->tracker_manager);
        $this->tracker2->setReturnReference('getTrackerManager', $this->tracker_manager);

        $this->tracker_manager->setReturnValue('userCanAdminAllProjectTrackers', false);

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
        $this->trr = new MockTracker_DateReminderManager();
        $this->tracker->setReturnReference('getDateReminderManager', $this->trr);
        $this->tracker1->setReturnReference('getDateReminderManager', $this->trr);
        $this->tracker2->setReturnReference('getDateReminderManager', $this->trr);
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
        $this->tracker->setReturnValue('getColor', 'inca_gray');
        $this->tracker1->setReturnValue('getGroupId', $group_id);
        $this->tracker1->setReturnValue('getId', 111);
        $this->tracker2->setReturnValue('getGroupId', $group_id);
        $this->tracker2->setReturnValue('getId', 112);


        $this->tracker->setReturnValue('getPermissionsByUgroupId', array(
            1 => array('PERM_1'),
            3 => array('PERM_2'),
            5 => array('PERM_3'),
            115 => array('PERM_3'),
        ));
        $this->tracker1->setReturnValue('getPermissionsByUgroupId', array(
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        ));
        $this->tracker2->setReturnValue('getPermissionsByUgroupId', array(
            1002 => array( 102 => 'PLUGIN_TRACKER_ADMIN'),
        ));

        $this->site_admin_user = mock('PFUser');
        $this->site_admin_user->setReturnValue('getId', 1);
        $this->site_admin_user->setReturnValue('isMember', false);
        $this->site_admin_user->setReturnValue('isSuperUser', true);
        $this->site_admin_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->site_admin_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->site_admin_user->setReturnValue('isLoggedIn', true);

        $this->project_admin_user = mock('PFUser');
        $this->project_admin_user->setReturnValue('getId', 123);
        $this->project_admin_user->setReturnValue('isMember', true, array($group_id, 'A'));
        $this->project_admin_user->setReturnValue('isMember', false, array(102));
        $this->project_admin_user->setReturnValue('isSuperUser', false);
        $this->project_admin_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->project_admin_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->project_admin_user->setReturnValue('isLoggedIn', true);

        $this->all_trackers_admin_user = mock('PFUser');
        $this->all_trackers_admin_user->setReturnValue('getId', 222);
        $this->all_trackers_admin_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->all_trackers_admin_user->setReturnValue('isMember', false, array(102));
        $this->all_trackers_admin_user->setReturnValue('isSuperUser', false);
        $this->all_trackers_admin_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->all_trackers_admin_user->setReturnValue('isMemberOfUGroup', true, array(1001, '*')); //1001 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->setReturnValue('isMemberOfUGroup', true, array(1002, '*')); //1002 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->setReturnValue('isLoggedIn', true);

        $this->tracker1_admin_user = mock('PFUser');
        $this->tracker1_admin_user->setReturnValue('getId', 333);
        $this->tracker1_admin_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->tracker1_admin_user->setReturnValue('isMember', false, array(102));
        $this->tracker1_admin_user->setReturnValue('isSuperUser', false);
        $this->tracker1_admin_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->tracker1_admin_user->setReturnValue('isMemberOfUGroup', true, array(1001, '*'));
        $this->tracker1_admin_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->tracker1_admin_user->setReturnValue('isLoggedIn', true);

        $this->tracker2_admin_user = mock('PFUser');
        $this->tracker2_admin_user->setReturnValue('getId', 444);
        $this->tracker2_admin_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->tracker2_admin_user->setReturnValue('isMember', false, array(102));
        $this->tracker2_admin_user->setReturnValue('isSuperUser', false);
        $this->tracker2_admin_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->tracker2_admin_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->tracker2_admin_user->setReturnValue('isMemberOfUGroup', true, array(1002, '*'));
        $this->tracker2_admin_user->setReturnValue('isLoggedIn', true);

        $this->project_member_user = mock('PFUser');
        $this->project_member_user->setReturnValue('getId', 555);
        $this->project_member_user->setReturnValue('isMember', false, array($group_id, 'A'));
        $this->project_member_user->setReturnValue('isMember', false, array(102));
        $this->project_member_user->setReturnValue('isSuperUser', false);
        $this->project_member_user->setReturnValue('isMember', true, array($group_id, 0));
        $this->project_member_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->project_member_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->project_member_user->setReturnValue('isTrackerAdmin', false);
        $this->project_member_user->setReturnValue('isLoggedIn', true);

        $this->registered_user = mock('PFUser');
        $this->registered_user->setReturnValue('getId', 777);
        $this->registered_user->setReturnValue('isMember', false);
        $this->registered_user->setReturnValue('isSuperUser', false);
        $this->registered_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->registered_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->registered_user->setReturnValue('isLoggedIn', true);

        $this->anonymous_user = mock('PFUser');
        $this->anonymous_user->setReturnValue('getId', 777);
        $this->anonymous_user->setReturnValue('isMember', false);
        $this->anonymous_user->setReturnValue('isSuperUser', false);
        $this->anonymous_user->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->anonymous_user->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));
        $this->anonymous_user->setReturnValue('isLoggedIn', false);

        // Users for tracker access perm tests
        $this->anonymous = mock('PFUser');
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

        $this->registered = mock('PFUser');
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

        $this->project_member = mock('PFUser');
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
        $this->project_member->setReturnValue('isMember', false, array(102));

        $this->project_admin = mock('PFUser');
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
        $this->project_admin->setReturnValue('isMember', false, array(102));

        $this->super_admin = mock('PFUser');
        $this->super_admin->setReturnValue('isSuperUser', true);
        $this->super_admin->setReturnValue('getId', 104);
        $this->super_admin->setReturnValue('isMemberOfUGroup', true, array('*', '*'));
        $this->super_admin->setReturnValue('isMemberOfUGroup', false, array(1001, '*'));
        $this->super_admin->setReturnValue('isMemberOfUGroup', false, array(1002, '*'));

        $this->tracker_submitter = mock('PFUser');
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
        $this->tracker_submitter->setReturnValue('isMember', false, array(102));

        $this->tracker_assignee = mock('PFUser');
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
        $this->tracker_assignee->setReturnValue('isMember', false, array(102));

        $this->tracker_submitterassignee = mock('PFUser');
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
        $this->tracker_submitterassignee->setReturnValue('isMember', false, array(102));

        $this->tracker_admin = mock('PFUser');
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
        $this->tracker_admin->setReturnValue('isMember', false, array(102));

        $this->all_trackers_forge_admin_user = mock('PFUser');
        $this->all_trackers_forge_admin_user->setReturnValue('getId', 888);
        $this->all_trackers_forge_admin_user->setReturnValue('isMember', false);
        $this->all_trackers_forge_admin_user->setReturnValue('isSuperUser', false);
        $this->all_trackers_forge_admin_user->setReturnValue('isMemberOfUGroup', false);
        $this->all_trackers_forge_admin_user->setReturnValue('isMemberOfUGroup', false);
        $this->all_trackers_forge_admin_user->setReturnValue('isLoggedIn', true);

        $this->workflow_factory = new MockWorkflowFactory();
        $this->tracker->setReturnReference('getWorkflowFactory', $this->workflow_factory);

        $this->formelement_factory = new MockTracker_FormElementFactory();
        $this->tracker->setReturnReference('getFormElementFactory', $this->formelement_factory);

        $this->report_factory = new MockTracker_ReportFactory();
        $this->tracker->setReturnReference('getReportFactory', $this->report_factory);

        $this->canned_response_factory = new MockTracker_CannedResponseFactory();
        $this->tracker->setReturnReference('getCannedResponseFactory', $this->canned_response_factory);

        $this->permission_controller = mock('Tracker_Permission_PermissionController');
        stub($this->tracker)->getPermissionController()->returns($this->permission_controller);

        $this->permission_controller1 = mock('Tracker_Permission_PermissionController');
        stub($this->tracker1)->getPermissionController()->returns($this->permission_controller1);

        $this->permission_controller2 = mock('Tracker_Permission_PermissionController');
        stub($this->tracker2)->getPermissionController()->returns($this->permission_controller2);

        $this->hierarchy = new Tracker_Hierarchy();
        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getHierarchy()->returns($this->hierarchy);
        $this->tracker->setReturnValue('getHierarchyFactory', $hierarchy_factory);

        $this->workflow_factory = mock('WorkflowFactory');
        WorkflowFactory::setInstance($this->workflow_factory);

        $this->user_manager = mock('UserManager');
        UserManager::setInstance($this->user_manager);

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
        WorkflowFactory::clearInstance();
        UserManager::clearInstance();
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

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // site admin can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }

    public function testPermsNewArtifactProjectAdmin() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // project admin can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->project_admin_user);
    }

    public function testPermsNewArtifactTrackerAdmin() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // tracker admin can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->all_trackers_admin_user);
    }

    public function testPermsNewArtifactProjectMember() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->tracker)->userCanView()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // project member can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->project_member_user);
    }

    public function testPermsNewArtifactRegisteredUser() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->tracker)->userCanView()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // registered user can submit artifacts
        $this->tracker->expectOnce('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->registered_user);
    }

    public function testUserCannotCreateArtifactIfTheyDoNotHaveSubmitPermissionsOnAtLeastOneField() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(false);
        $tracker_field2 = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field2)->userCanSubmit()->returns(false);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field,
            $tracker_field2
        ));

        // registered user can submit artifacts
        $this->tracker->expectNever('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->registered_user);
    }

    public function testUserCanCreateArtifactEvenIfTheyDoNotHaveSubmitPermissionsOnAllRequiredFields() {
        $request_new_artifact = new MockCodendi_Request($this);
        $request_new_artifact->setReturnValue('get', 'new-artifact', array('func'));

        $tracker_field = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field)->userCanSubmit()->returns(false);
        stub($tracker_field)->isRequired()->returns(true);
        $tracker_field2 = mock('Tracker_FormElement_Field_Text');
        stub($tracker_field2)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field,
            $tracker_field2
        ));

        // registered user can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
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

    public function itCachesTrackerAdminPermission()
    {
        $user = mock('PFUser');
        stub($user)->getId()->returns(101);
        $user->expectOnce('isSuperUser');

        $this->tracker->userIsAdmin($user);
        $this->tracker->userIsAdmin($user);
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
        expect($this->permission_controller)->process()->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->site_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerProjectAdmin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));

        // project admin can access tracker admin part
        expect($this->permission_controller)->process()->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->project_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTrackerAdmin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->all_trackers_admin_user);
        expect($this->permission_controller2)->process()->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTracker1Admin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker1_admin_user);
        expect($this->permission_controller2)->process()->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTracker2Admin() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker2_admin_user);
        expect($this->permission_controller2)->process()->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerProjectMember() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));

        // project member can NOT access tracker admin part
        expect($this->permission_controller)->process()->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->project_member_user);
    }
    public function testPermsAdminPermsTrackerTrackerRegisteredUser() {
        $request_admin_perms_tracker_tracker = new MockCodendi_Request($this);
        $request_admin_perms_tracker_tracker->setReturnValue('get', 'admin-perms-tracker', array('func'));

        // registered user can NOT access tracker admin part
        expect($this->permission_controller)->process()->never();
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
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

        // site admin can access tracker admin part
        $this->tracker->expectOnce('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->site_admin_user);
    }
    public function testPermsAdminWorkflowTrackerProjectAdmin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

        // project admin can access tracker admin part
        $this->tracker->expectOnce('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTrackerAdmin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getWorkflowManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expectOnce('getWorkflowManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTracker1Admin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

        // tracker admin can access tracker admin part
        $this->tracker1->expectOnce('getWorkflowManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
        $this->tracker2->expectNever('getWorkflowManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTracker2Admin() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

        // tracker admin can access tracker admin part
        $this->tracker1->expectNever('getWorkflowManager');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
        $this->tracker2->expectOnce('getWorkflowManager');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminWorkflowTrackerProjectMember() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

        // project member can NOT access tracker admin part
        $this->tracker->expectNever('getWorkflowManager');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_member_user);
    }
    public function testPermsAdminWorkflowTrackerRegisteredUser() {
        $request_admin_workflow_tracker = new MockCodendi_Request($this);
        $request_admin_workflow_tracker->setReturnValue('get', Workflow::FUNC_ADMIN_TRANSITIONS, array('func'));

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
        $t_access_anonymous->setReturnValue('getProject', $this->project);
        $perms = array(
                1 => array( 101 => 'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_anonymous->setReturnReference('getPermissionsByUgroupId', $perms);
        $t_access_anonymous->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_anonymous->setReturnReference('getUserManager', $this->user_manager);

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
        $t_access_registered = new TrackerTestVersionForAccessPerms();
        $t_access_registered->setReturnValue('getId', 2);
        $t_access_registered->setReturnValue('getGroupId', 101);
        $t_access_registered->setReturnValue('getProject', $this->project);
        $t_access_registered->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_registered->setReturnReference('getUserManager', $this->user_manager);
        $perms = array(
                2 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_registered->setReturnReference('getPermissionsByUgroupId', $perms);

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
        $t_access_members = new TrackerTestVersionForAccessPerms();
        $t_access_members->setReturnValue('getId', 3);
        $t_access_members->setReturnValue('getGroupId', 101);
        $t_access_members->setReturnValue('getProject', $this->project);
        $perms = array(
                3 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_members->setReturnReference('getPermissionsByUgroupId', $perms);
        $t_access_members->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_members->setReturnReference('getUserManager', $this->user_manager);

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

    public function testAccessPermsTrackerAdminAllProjects() {
        $t_access_members = new TrackerTestVersionForAccessPerms();
        $t_access_members->setReturnValue('getId', 3);
        $t_access_members->setReturnValue('getGroupId', 101);
        $t_access_members->setReturnValue('getProject', $this->project);
        $perms = array(
                3 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_members->setReturnReference('getPermissionsByUgroupId', $perms);

        $tracker_manager = mock('TrackerManager');
        $t_access_members->setReturnReference('getTrackerManager', $tracker_manager);
        $t_access_members->setReturnReference('getUserManager', $this->user_manager);

        stub($tracker_manager)->userCanAdminAllProjectTrackers($this->all_trackers_forge_admin_user)->returns(true);

        $this->assertTrue($t_access_members->userCanView($this->all_trackers_forge_admin_user));
    }

    public function testAccessPermsAdminFullAccess() {
        $t_access_admin = new TrackerTestVersionForAccessPerms();
        $t_access_admin->setReturnValue('getId', 4);
        $t_access_admin->setReturnValue('getGroupId', 101);
        $t_access_admin->setReturnValue('getProject', $this->project);
        $perms = array(
                4 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
                );
        $t_access_admin->setReturnReference('getPermissionsByUgroupId', $perms);
        $t_access_admin->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_admin->setReturnReference('getUserManager', $this->user_manager);

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
        $t_access_submitter = new TrackerTestVersionForAccessPerms();
        $t_access_submitter->setReturnValue('getId', 5);
        $t_access_submitter->setReturnValue('getGroupId', 101);
        $t_access_submitter->setReturnValue('getProject', $this->project);
        $perms = array(
                4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                138 => array(101=>'PLUGIN_TRACKER_ACCESS_SUBMITTER'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_submitter->setReturnReference('getPermissionsByUgroupId', $perms);
        $t_access_submitter->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_submitter->setReturnReference('getUserManager', $this->user_manager);

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
        $t_access_assignee = new TrackerTestVersionForAccessPerms();
        $t_access_assignee->setReturnValue('getId', 6);
        $t_access_assignee->setReturnValue('getGroupId', 101);
        $t_access_assignee->setReturnValue('getProject', $this->project);
        $perms = array(
                4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                196 => array(101=>'PLUGIN_TRACKER_ACCESS_ASSIGNEE'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_assignee->setReturnReference('getPermissionsByUgroupId', $perms);
        $t_access_assignee->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_assignee->setReturnReference('getUserManager', $this->user_manager);

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
        $t_access_submitterassignee  = new TrackerTestVersionForAccessPerms();
        $t_access_submitterassignee->setReturnValue('getId', 7);
        $t_access_submitterassignee->setReturnValue('getGroupId', 101);
        $t_access_submitterassignee->setReturnValue('getProject', $this->project);
        $t_access_submitterassignee->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_submitterassignee->setReturnReference('getUserManager', $this->user_manager);

        $perms = array(
                4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
                138 => array(101=>'PLUGIN_TRACKER_ACCESS_SUBMITTER'),
                196 => array(101=>'PLUGIN_TRACKER_ACCESS_ASSIGNEE'),
                1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
            );
        $t_access_submitterassignee->setReturnReference('getPermissionsByUgroupId', $perms);

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

    public function testAccessPermsPrivateProject() {
        $t_access_registered  = new TrackerTestVersionForAccessPerms();
        $t_access_registered->setReturnValue('getId', 7);
        $t_access_registered->setReturnValue('getGroupId', 102);
        $t_access_registered->setReturnValue('getProject', $this->project_private);

        $perms = array(
               2    => array( 102 => 'PLUGIN_TRACKER_ACCESS_FULL'),
               1003 => array( 102 => 'PLUGIN_TRACKER_ADMIN'),
        );

        $t_access_registered->setReturnReference('getPermissionsByUgroupId', $perms);
        $t_access_registered->setReturnReference('getTrackerManager', $this->tracker_manager);
        $t_access_registered->setReturnReference('getUserManager', $this->user_manager);

        $this->assertFalse($t_access_registered->userCanView($this->anonymous));
        $this->assertFalse($t_access_registered->userCanView($this->registered));
        $this->assertFalse($t_access_registered->userCanView($this->project_member));
        $this->assertFalse($t_access_registered->userCanView($this->project_admin));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_submitter));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_assignee));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_submitterassignee));
        $this->assertFalse($t_access_registered->userCanView($this->tracker_admin));

        $this->assertTrue($t_access_registered->userCanView($this->super_admin));
    }

    public function testHasErrorNoError()
    {
        $header = array('summary', 'details');
        $lines = array(
                    array('summary 1', 'details 1'),
                    array('summary 2', 'details 2'),
                 );
        $field1 = new MockTracker_FormElement_Field_String();
        $field2 = new MockTracker_FormElement_Field_String();
        stub($this->formelement_factory)->getUsedFields()->returns(array($field1, $field2));

        $field1->setReturnValue('validateFieldWithPermissionsAndRequiredStatus', true);
        $field2->setReturnValue('validateFieldWithPermissionsAndRequiredStatus', true);

        $field1->setReturnValue('getId', 1);
        $field2->setReturnValue('getId', 2);

        $field1->setReturnValue('getFieldDataFromCSVValue', 'summary 1', array('summary 1'));
        $field1->setReturnValue('getFieldDataFromCSVValue', 'summary 2', array('summary 2'));

        $field2->setReturnValue('getFieldDataFromCSVValue', 'details 1', array('details 1'));
        $field2->setReturnValue('getFieldDataFromCSVValue', 'details 2', array('details 2'));

        $field1->setReturnValue('isCSVImportable', true);
        $field2->setReturnValue('isCSVImportable', true);

        $this->formelement_factory->setReturnReference('getUsedFieldByName', $field1, array(110, 'summary'));
        $this->formelement_factory->setReturnReference('getUsedFieldByName', $field2, array(110, 'details'));

        $artifact = new MockTracker_Artifact();

        $af = new MockTracker_ArtifactFactory();
        $this->tracker->setReturnReference('getTrackerArtifactFactory', $af);
        $this->tracker->setReturnValue('aidExists', false, array('0'));

        $um = new MockUserManager();
        $u = mock('PFUser');
        $u->setReturnValue('getId', '107');
        $this->tracker->setReturnReference('getUserManager', $um);
        $um->setReturnReference('getCurrentUser', $u);


        $af->setReturnReference('getInstanceFromRow', $artifact);

        stub($this->workflow_factory)->getGlobalRulesManager()->returns(mock('Tracker_RulesManager'));

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
        $factory->expectOnce('createFormElement', array($tracker , $data['type'], $data, false, false));
        $sharedFactory->expectNever('createFormElement');

        $tracker->createFormElement($data['type'], $data, $user);
    }

    public function testCreateFormElementDispatchesToSharedField() {
        $data = array('type' => 'shared');

        list($tracker, $factory, $sharedFactory, $user) = $this->GivenATrackerAndItsFactories();
        $factory->expectNever('createFormElement');
        $sharedFactory->expectOnce('createFormElement', array($tracker , $data, $user, false, false));

        $tracker->createFormElement($data['type'], $data, $user);
    }

    private function GivenATrackerAndItsFactories() {
        $tracker = new Tracker(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null);
        $factory = new MockTracker_FormElementFactory();
        $tracker->setFormElementFactory($factory);
        $sharedFactory = new MockTracker_SharedFormElementFactory();
        $tracker->setSharedFormElementFactory($sharedFactory);
        $user = mock('PFUser');
        return array($tracker, $factory, $sharedFactory, $user);
    }
}

class Tracker_ExportToXmlTest extends TuleapTestCase {

    private $tracker;
    private $formelement_factory;
    private $workflow_factory;
    private $hierarchy;

    public function setUp() {
        parent::setUp();
        $this->tracker = new TrackerTestVersion();
        stub($this->tracker)->getID()->returns(110);
        stub($this->tracker)->getColor()->returns('inca_gray');
        stub($this->tracker)->getUserManager()->returns(mock('UserManager'));

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->tracker)->getFormElementFactory()->returns($this->formelement_factory);

        $this->workflow_factory = mock('WorkflowFactory');
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(mock('Tracker_RulesManager'));
        stub($this->tracker)->getWorkflowFactory()->returns($this->workflow_factory);

        $this->hierarchy = new Tracker_Hierarchy();
        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getHierarchy()->returns($this->hierarchy);
        stub($this->tracker)->getHierarchyFactory()->returns($hierarchy_factory);

        $tcrm = mock('Tracker_CannedResponseManager');
        stub($this->tracker)->getCannedResponseManager()->returns($tcrm);

        $canned_response_factory = mock('Tracker_CannedResponseFactory');
        stub($this->tracker)->getCannedResponseFactory()->returns($canned_response_factory);

        $tsm = mock('Tracker_SemanticManager');
        stub($this->tracker)->getTrackerSemanticManager()->returns($tsm);

        $report_factory = mock('Tracker_ReportFactory');
        stub($this->tracker)->getReportFactory()->returns($report_factory);

        $webhook_xml_exporter = \Mockery::mock(\Tuleap\Tracker\Webhook\WebhookXMLExporter::class);
        $webhook_xml_exporter->shouldReceive('exportTrackerWebhooksInXML')->once();
        stub($this->tracker)->getWebhookXMLExporter()->returns($webhook_xml_exporter);
    }

    public function testPermissionsExport() {
        stub($this->tracker)->getPermissionsByUgroupId()->returns(array(
            1   => array('PERM_1'),
            3   => array('PERM_2'),
            5   => array('PERM_3'),
            115 => array('PERM_3'),
        ));
        $ugroups = array(
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        );
        stub($this->tracker)->getProjectUgroups()->returns($ugroups);

        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());

        stub($this->workflow_factory)->getGlobalRulesManager()->returns(
            mock('Tracker_RulesManager')
        );

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

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
    }

    public function itExportsTheTrackerID() {
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(mock('Tracker_RulesManager'));

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEqual((string)$attributes['id'], 'T110');
    }

    public function itExportsNoParentIfNotInAHierarchy() {
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(mock('Tracker_RulesManager'));

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEqual((string)$attributes['parent_id'], "0");
    }

    public function itExportsTheParentId() {
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(mock('Tracker_RulesManager'));
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());

        $this->hierarchy->addRelationship(9001, 110);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEqual((string)$attributes['parent_id'], "T9001");
    }

    public function itExportsTheTrackerColor() {
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(mock('Tracker_RulesManager'));

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $color = $xml->color;
        $this->assertEqual((string)$color, 'inca_gray');
    }

}

class Tracker_WorkflowTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->tracker_id = 12;
        $this->tracker = partial_mock('Tracker', array('getWorkflowFactory'));
        $this->tracker->setId($this->tracker_id);

        $this->workflow_factory = mock('WorkflowFactory');
        stub($this->tracker)->getWorkflowFactory()->returns($this->workflow_factory);
    }

    public function itHasADefaultWorkflow() {
        $workflow = aWorkflow()->withTrackerId($this->tracker_id)->build();
        stub($this->workflow_factory)->getWorkflowByTrackerId()->returns(false);
        stub($this->workflow_factory)->getWorkflowWithoutTransition()->returns($workflow);
        $this->assertIdentical($this->tracker->getWorkflow(), $workflow);
    }

    public function itAlwaysHaveTheSameDefaultWorkflow() {
        stub($this->workflow_factory)->getWorkflowByTrackerId()->returns(false);
        stub($this->workflow_factory)->getWorkflowWithoutTransition()->returnsAt(0, aWorkflow()->withTrackerId(12)->build());
        stub($this->workflow_factory)->getWorkflowWithoutTransition()->returnsAt(1, aWorkflow()->withTrackerId(33)->build());
        $this->assertIdentical($this->tracker->getWorkflow(), $this->tracker->getWorkflow());
    }

    public function itHasAWorkflowFromTheFactoryWhenThereAreTransitions() {
        $workflow = aWorkflow()->withTrackerId($this->tracker_id)->build();
        stub($this->workflow_factory)->getWorkflowByTrackerId($this->tracker_id)->returns($workflow);
        $this->assertIdentical($this->tracker->getWorkflow(), $workflow);
    }
}


class Tracker_getParentTest extends TuleapTestCase {

    private $tracker;
    private $tracker_factory;

    public function setUp() {
        parent::setUp();
        $this->tracker_factory = mock('TrackerFactory');
        $this->tracker = partial_mock('Tracker', array('getParentId', 'getTrackerFactory'));
        stub($this->tracker)->getTrackerFactory()->returns($this->tracker_factory);
    }

    public function itReturnsNullWhenItHasNoParentFromAccessor() {
        $tracker = aTracker()->build();
        $tracker->setParent(null);
        $this->assertIdentical($tracker->getParent(), null);
    }

    public function itReturnsParentWhenParentWasSetByAccessor() {
        $parent  = aTracker()->build();
        $tracker = aTracker()->build();
        $tracker->setParent($parent);
        $this->assertIdentical($tracker->getParent(), $parent);
    }

    public function itReturnsNullWhenItHasNoParentFromDb() {
        stub($this->tracker)->getParentId()->returns(null);
        $this->assertIdentical($this->tracker->getParent(), null);
    }

    public function itReturnsNullWhenParentNotFoundInDb() {
        stub($this->tracker_factory)->getTrackerById(15)->returns(null);
        stub($this->tracker)->getParentId()->returns(15);
        $this->assertIdentical($this->tracker->getParent(), null);
    }

    public function itReturnsParentWhenFetchedFromDb() {
        $parent  = aTracker()->build();
        stub($this->tracker_factory)->getTrackerById(15)->returns($parent);
        stub($this->tracker)->getParentId()->returns(15);
        $this->assertIdentical($this->tracker->getParent(), $parent);
    }

    public function itDoesntFetchParentTwiceWhenThereIsParent() {
        $parent  = aTracker()->build();
        stub($this->tracker_factory)->getTrackerById(15)->returns($parent);
        stub($this->tracker)->getParentId()->returns(15);

        expect($this->tracker_factory)->getTrackerById(15)->once();

        $this->tracker->getParent();
        $this->tracker->getParent();
    }

    public function itDoesntFetchParentTwiceWhenOrphan() {
        stub($this->tracker_factory)->getTrackerById(15)->returns(null);
        stub($this->tracker)->getParentId()->returns(15);

        expect($this->tracker_factory)->getTrackerById(15)->once();

        $this->tracker->getParent();
        $this->tracker->getParent();
    }
}
