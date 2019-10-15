<?php
/**
 * Copyright (c) Enalean SAS, 2011 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\TrackerColor;

require_once __DIR__ . '/bootstrap.php';

class Tracker_FormElement_InterfaceTestVersion implements Tracker_FormElement_Interface
{
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

class TrackerTest extends TuleapTestCase
{

    private $all_trackers_admin_user;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturns(101);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('isActive')->andReturns(true);

        $this->project_private = \Mockery::spy(\Project::class);
        $this->project_private->shouldReceive('getID')->andReturns(102);
        $this->project_private->shouldReceive('isPublic')->andReturns(false);
        $this->project_private->shouldReceive('isActive')->andReturns(true);

        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker1 = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker2 = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker_manager = \Mockery::spy(\TrackerManager::class);

        $this->tracker->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
        $this->tracker1->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
        $this->tracker2->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);

        $this->tracker_manager->shouldReceive('userCanAdminAllProjectTrackers')->andReturns(false);

        $this->tf = \Mockery::spy(\TrackerFactory::class);
        $this->tracker->shouldReceive('getTrackerFactory')->andReturns($this->tf);
        $this->tracker1->shouldReceive('getTrackerFactory')->andReturns($this->tf);
        $this->tracker2->shouldReceive('getTrackerFactory')->andReturns($this->tf);
        $this->tsm = \Mockery::spy(\Tracker_SemanticManager::class);
        $this->tracker->shouldReceive('getTrackerSemanticManager')->andReturns($this->tsm);
        $this->tracker1->shouldReceive('getTrackerSemanticManager')->andReturns($this->tsm);
        $this->tracker2->shouldReceive('getTrackerSemanticManager')->andReturns($this->tsm);
        $this->tnm = \Mockery::spy(\Tracker_NotificationsManager::class);
        $this->tracker->shouldReceive('getNotificationsManager')->andReturns($this->tnm);
        $this->tracker1->shouldReceive('getNotificationsManager')->andReturns($this->tnm);
        $this->tracker2->shouldReceive('getNotificationsManager')->andReturns($this->tnm);
        $this->trr = \Mockery::spy(\Tracker_DateReminderManager::class);
        $this->tracker->shouldReceive('getDateReminderManager')->andReturns($this->trr);
        $this->tracker1->shouldReceive('getDateReminderManager')->andReturns($this->trr);
        $this->tracker2->shouldReceive('getDateReminderManager')->andReturns($this->trr);
        $this->tcrm = \Mockery::spy(\Tracker_CannedResponseManager::class);
        $this->tracker->shouldReceive('getCannedResponseManager')->andReturns($this->tcrm);
        $this->tracker1->shouldReceive('getCannedResponseManager')->andReturns($this->tcrm);
        $this->tracker2->shouldReceive('getCannedResponseManager')->andReturns($this->tcrm);
        $this->wm = \Mockery::spy(\WorkflowManager::class);
        $this->tracker->shouldReceive('getWorkflowManager')->andReturns($this->wm);
        $this->tracker1->shouldReceive('getWorkflowManager')->andReturns($this->wm);
        $this->tracker2->shouldReceive('getWorkflowManager')->andReturns($this->wm);
        $group_id = 999;
        $this->tracker->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker->shouldReceive('getId')->andReturns(110);
        $this->tracker->shouldReceive('getColor')->andReturns(TrackerColor::default());
        $this->tracker1->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker1->shouldReceive('getId')->andReturns(111);
        $this->tracker2->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker2->shouldReceive('getId')->andReturns(112);

        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturns(array(
            1 => array('PERM_1'),
            3 => array('PERM_2'),
            5 => array('PERM_3'),
            115 => array('PERM_3'),
        ));
        $this->tracker1->shouldReceive('getPermissionsByUgroupId')->andReturns(array(
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        ));
        $this->tracker2->shouldReceive('getPermissionsByUgroupId')->andReturns(array(
            1002 => array( 102 => 'PLUGIN_TRACKER_ADMIN'),
        ));

        $this->site_admin_user = \Mockery::spy(\PFUser::class);
        $this->site_admin_user->shouldReceive('getId')->andReturns(1);
        $this->site_admin_user->shouldReceive('isMember')->andReturns(false);
        $this->site_admin_user->shouldReceive('isSuperUser')->andReturns(true);
        $this->site_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->site_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->site_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->project_admin_user = \Mockery::spy(\PFUser::class);
        $this->project_admin_user->shouldReceive('getId')->andReturns(123);
        $this->project_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(true);
        $this->project_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $this->project_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->project_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->project_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->project_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->all_trackers_admin_user = \Mockery::spy(\PFUser::class);
        $this->all_trackers_admin_user->shouldReceive('getId')->andReturns(222);
        $this->all_trackers_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $this->all_trackers_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(true); //1001 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(true); //1002 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->tracker1_admin_user = \Mockery::spy(\PFUser::class);
        $this->tracker1_admin_user->shouldReceive('getId')->andReturns(333);
        $this->tracker1_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $this->tracker1_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(true);
        $this->tracker1_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->tracker2_admin_user = \Mockery::spy(\PFUser::class);
        $this->tracker2_admin_user->shouldReceive('getId')->andReturns(444);
        $this->tracker2_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $this->tracker2_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(true);
        $this->tracker2_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->project_member_user = \Mockery::spy(\PFUser::class);
        $this->project_member_user->shouldReceive('getId')->andReturns(555);
        $this->project_member_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $this->project_member_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $this->project_member_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->project_member_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $this->project_member_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->project_member_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->project_member_user->shouldReceive('isTrackerAdmin')->andReturns(false);
        $this->project_member_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->registered_user = \Mockery::spy(\PFUser::class);
        $this->registered_user->shouldReceive('getId')->andReturns(777);
        $this->registered_user->shouldReceive('isMember')->andReturns(false);
        $this->registered_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->registered_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->registered_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->registered_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        $this->tracker->shouldReceive('getWorkflowFactory')->andReturns($this->workflow_factory);

        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->tracker->shouldReceive('getFormElementFactory')->andReturns($this->formelement_factory);

        $this->report_factory = \Mockery::spy(\Tracker_ReportFactory::class);
        $this->tracker->shouldReceive('getReportFactory')->andReturns($this->report_factory);

        $this->canned_response_factory = \Mockery::spy(\Tracker_CannedResponseFactory::class);
        $this->tracker->shouldReceive('getCannedResponseFactory')->andReturns($this->canned_response_factory);

        $this->permission_controller = \Mockery::spy(\Tracker_Permission_PermissionController::class);
        stub($this->tracker)->getPermissionController()->returns($this->permission_controller);

        $this->permission_controller1 = \Mockery::spy(\Tracker_Permission_PermissionController::class);
        stub($this->tracker1)->getPermissionController()->returns($this->permission_controller1);

        $this->permission_controller2 = \Mockery::spy(\Tracker_Permission_PermissionController::class);
        stub($this->tracker2)->getPermissionController()->returns($this->permission_controller2);

        $this->hierarchy = new Tracker_Hierarchy();
        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getHierarchy()->returns($this->hierarchy);
        $this->tracker->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        WorkflowFactory::setInstance($this->workflow_factory);

        $this->user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($this->user_manager);

        $GLOBALS['Response'] = \Mockery::spy(\Layout::class);

        $GLOBALS['UGROUPS'] = array(
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        );
    }

    public function tearDown()
    {
        WorkflowFactory::clearInstance();
        UserManager::clearInstance();
        unset($this->site_admin_user);
        unset($this->project_admin_user);
        unset($this->all_trackers_admin_user);
        unset($this->tracker1_admin_user);
        unset($this->tracker2_admin_user);
        unset($this->project_member_user);
        unset($this->registered_user);
        parent::tearDown();
    }

    // New artifact permissions
    public function testPermsNewArtifactSiteAdmin()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // site admin can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }

    public function testPermsNewArtifactProjectAdmin()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // project admin can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->project_admin_user);
    }

    public function testPermsNewArtifactTrackerAdmin()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // tracker admin can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->all_trackers_admin_user);
    }

    public function testPermsNewArtifactProjectMember()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->tracker)->userCanView()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // project member can submit artifacts
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->project_member_user);
    }

    public function testPermsNewArtifactRegisteredUser()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(true);
        stub($this->tracker)->userCanView()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field
        ));

        // registered user can submit artifacts
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->registered_user);
    }

    public function testUserCannotCreateArtifactIfTheyDoNotHaveSubmitPermissionsOnAtLeastOneField()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(false);
        $tracker_field2 = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field2)->userCanSubmit()->returns(false);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field,
            $tracker_field2
        ));

        $this->tracker->shouldReceive('userCanView')->with($this->registered_user)->andReturn(true);

        // registered user can submit artifacts
        $this->tracker->shouldReceive('displaySubmit')->never();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->registered_user);
    }

    public function testUserCanCreateArtifactEvenIfTheyDoNotHaveSubmitPermissionsOnAllRequiredFields()
    {
        $request_new_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->with('func')->andReturns('new-artifact');

        $tracker_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field)->userCanSubmit()->returns(false);
        stub($tracker_field)->isRequired()->returns(true);
        $tracker_field2 = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($tracker_field2)->userCanSubmit()->returns(true);
        stub($this->formelement_factory)->getUsedFields()->returns(array(
            $tracker_field,
            $tracker_field2
        ));

        // registered user can submit artifacts
        stub($this->tracker)->userCanView()->returns(true);
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->registered_user);
    }

    // Delete tracker permissions
    public function testPermsDeleteTrackerSiteAdmin()
    {
        $request_delete_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_delete_tracker->shouldReceive('get')->with('func')->andReturns('delete');

        // site admin can delete trackers
        $this->tf->shouldReceive('markAsDeleted')->once();
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->site_admin_user);
    }
    public function testPermsDeleteTrackerProjectAdmin()
    {
        $request_delete_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_delete_tracker->shouldReceive('get')->with('func')->andReturns('delete');

        // project admin can delete trackers
        $this->tf->shouldReceive('markAsDeleted')->once();
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->project_admin_user);
    }
    public function testPermsDeleteTrackerTrackerAdmin()
    {
        $request_delete_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_delete_tracker->shouldReceive('get')->with('func')->andReturns('delete');

        // tracker admin can NOT delete trackers if he's not project admin
        $this->tf->shouldReceive('markAsDeleted')->never();
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsDeleteTrackerProjectMember()
    {
        $request_delete_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_delete_tracker->shouldReceive('get')->with('func')->andReturns('delete');

        // project member can NOT delete tracker
        $this->tf->shouldReceive('markAsDeleted')->never();
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->project_member_user);
    }
    public function testPermsDeleteTrackerRegisteredUser()
    {
        $request_delete_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_delete_tracker->shouldReceive('get')->with('func')->andReturns('delete');

        // registered user can NOT delete trackers
        $this->tf->shouldReceive('markAsDeleted')->never();
        $this->tracker->process($this->tracker_manager, $request_delete_tracker, $this->registered_user);
    }

    // Tracker admin permissions
    public function testPermsAdminTrackerSiteAdmin()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // site admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->site_admin_user);
    }
    public function testPermsAdminTrackerProjectAdmin()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // project admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_admin_user);
    }
    public function testPermsAdminTrackerTrackerAdmin()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminTrackerTracker1Admin()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminTrackerTracker2Admin()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminTrackerProjectMember()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // project member can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_member_user);
    }
    public function testPermsAdminTrackerRegisteredUser()
    {
        $request_admin_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->with('func')->andReturns('admin');

        // registered user can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->registered_user);
    }

    public function itCachesTrackerAdminPermission()
    {
        $user = \Mockery::spy(\PFUser::class);
        stub($user)->getId()->returns(101);
        $user->shouldReceive('isSuperUser')->once();

        $this->tracker->userIsAdmin($user);
        $this->tracker->userIsAdmin($user);
    }

    // Tracker admin edit option permissions
    public function testPermsAdminEditOptionsTrackerSiteAdmin()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // site admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->site_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerProjectAdmin()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // project admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerTrackerAdmin()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminOptions')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->all_trackers_admin_user);
        $this->tracker2->shouldReceive('displayAdminOptions')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerTracker1Admin()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminOptions')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker1_admin_user);
        $this->tracker2->shouldReceive('displayAdminOptions')->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerTracker2Admin()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminOptions')->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker2_admin_user);
        $this->tracker2->shouldReceive('displayAdminOptions')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminEditOptionsTrackerProjectMember()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // project member can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_member_user);
    }
    public function testPermsAdminEditOptionsTrackerRegisteredUser()
    {
        $request_admin_editoptions_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->with('func')->andReturns('admin-editoptions');

        // registered user can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->registered_user);
    }

    // Tracker "admin perms" permissions
    public function testPermsAdminPermsTrackerSiteAdmin()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // site admin can access tracker admin part
        expect($this->permission_controller)->process()->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->site_admin_user);
    }
    public function testPermsAdminPermsTrackerProjectAdmin()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // project admin can access tracker admin part
        expect($this->permission_controller)->process()->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerAdmin()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
        expect($this->permission_controller2)->process()->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminPermsTrackerTracker1Admin()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
        expect($this->permission_controller2)->process()->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminPermsTrackerTracker2Admin()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
        expect($this->permission_controller2)->process()->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminPermsTrackerProjectMember()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // project member can NOT access tracker admin part
        expect($this->permission_controller)->process()->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_member_user);
    }
    public function testPermsAdminPermsTrackerRegisteredUser()
    {
        $request_admin_perms_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms');

        // registered user can NOT access tracker admin part
        expect($this->permission_controller)->process()->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->registered_user);
    }

    // Tracker "admin perms tracker" permissions
    public function testPermsAdminPermsTrackerTrackerSiteAdmin()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // site admin can access tracker admin part
        expect($this->permission_controller)->process()->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->site_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerProjectAdmin()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // project admin can access tracker admin part
        expect($this->permission_controller)->process()->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->project_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTrackerAdmin()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->all_trackers_admin_user);
        expect($this->permission_controller2)->process()->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTracker1Admin()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker1_admin_user);
        expect($this->permission_controller2)->process()->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerTracker2Admin()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // tracker admin can access tracker admin part
        expect($this->permission_controller1)->process()->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker2_admin_user);
        expect($this->permission_controller2)->process()->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminPermsTrackerTrackerProjectMember()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // project member can NOT access tracker admin part
        expect($this->permission_controller)->process()->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->project_member_user);
    }
    public function testPermsAdminPermsTrackerTrackerRegisteredUser()
    {
        $request_admin_perms_tracker_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->with('func')->andReturns('admin-perms-tracker');

        // registered user can NOT access tracker admin part
        expect($this->permission_controller)->process()->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->registered_user);
    }

    // Tracker "admin form elements" permissions
    public function testPermsAdminFormElementTrackerSiteAdmin()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // site admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->site_admin_user);
    }
    public function testPermsAdminFormElementTrackerProjectAdmin()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // project admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_admin_user);
    }
    public function testPermsAdminFormElementTrackerTrackerAdmin()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_formelement_tracker, $this->all_trackers_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_formelement_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminFormElementTrackerTracker1Admin()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker1_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminFormElementTrackerTracker2Admin()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker2_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_formelement_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminFormElementTrackerProjectMember()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // project member can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_member_user);
    }
    public function testPermsAdminFormElementTrackerRegisteredUser()
    {
        $request_admin_formelement_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->with('func')->andReturns('admin-formElements');

        // registered user can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->registered_user);
    }

    // Tracker "admin semantic" permissions
    public function testPermsAdminSemanticTrackerSiteAdmin()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // site admin can access tracker admin part
        $this->tsm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->site_admin_user);
    }
    public function testPermsAdminSemanticTrackerProjectAdmin()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // project admin can access tracker admin part
        $this->tsm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_admin_user);
    }
    public function testPermsAdminSemanticTrackerTrackerAdmin()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // tracker admin can access tracker admin part
        $this->tsm->shouldReceive('process')->times(2);
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->all_trackers_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminSemanticTrackerTracker1Admin()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // tracker admin can access tracker admin part
        $this->tsm->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminSemanticTrackerTracker2Admin()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
        $this->tsm->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminSemanticTrackerProjectMember()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // project member can NOT access tracker admin part
        $this->tsm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_member_user);
    }
    public function testPermsAdminSemanticTrackerRegisteredUser()
    {
        $request_admin_semantic_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->with('func')->andReturns('admin-semantic');

        // registered user can NOT access tracker admin part
        $this->tsm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->registered_user);
    }

    // Tracker "admin canned" permissions
    public function testPermsAdminCannedTrackerSiteAdmin()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // site admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->site_admin_user);
    }
    public function testPermsAdminCannedTrackerProjectAdmin()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // project admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_admin_user);
    }
    public function testPermsAdminCannedTrackerTrackerAdmin()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // tracker admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->times(2);
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminCannedTrackerTracker1Admin()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // tracker admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminCannedTrackerTracker2Admin()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminCannedTrackerProjectMember()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // project member can NOT access tracker admin part
        $this->tcrm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_member_user);
    }
    public function testPermsAdminCannedTrackerRegisteredUser()
    {
        $request_admin_canned_tracker = \Mockery::spy(\Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->with('func')->andReturns('admin-canned');

        // registered user can NOT access tracker admin part
        $this->tcrm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->registered_user);
    }

    // Tracker "admin workflow" permissions
    public function testPermsAdminWorkflowTrackerSiteAdmin()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // site admin can access tracker admin part
        $this->wm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->site_admin_user);
    }
    public function testPermsAdminWorkflowTrackerProjectAdmin()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // project admin can access tracker admin part
        $this->wm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTrackerAdmin()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // tracker admin can access tracker admin part
        $this->wm->shouldReceive('process')->times(2);
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->all_trackers_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->all_trackers_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTracker1Admin()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // tracker admin can access tracker admin part
        $this->wm->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
    }
    public function testPermsAdminWorkflowTrackerTracker2Admin()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
        $this->wm->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
    }
    public function testPermsAdminWorkflowTrackerProjectMember()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // project member can NOT access tracker admin part
        $this->wm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_member_user);
    }
    public function testPermsAdminWorkflowTrackerRegisteredUser()
    {
        $request_admin_workflow_tracker = \Mockery::spy(\HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->with('func')->andReturns(Workflow::FUNC_ADMIN_TRANSITIONS);

        // registered user can NOT access tracker admin part
        $this->wm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->registered_user);
    }

    public function testHasUnknownAidCreateMode()
    {
        $header = array('summary', 'details');
        $lines = array(
                    array('summary 1', 'details 1'),
                    array('summary 2', 'details 2'),
                    array('summary 3', 'details 3'),
                    array('summary 4', 'details 4'),
                 );

        $this->assertFalse($this->tracker->hasUnknownAid($header, $lines));
    }

    public function testHasUnknownAidUpdateModeNoError()
    {
        $header = array('aid','summary', 'details');
        $lines = array(
                    array('1','summary 1', 'details 1'),
                    array('2','summary 2', 'details 2'),
                    array('3','summary 3', 'details 3'),
                    array('4','summary 4', 'details 4'),
                 );

        $artifact1 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact1->shouldReceive('getId')->andReturns('1');
        $artifact2 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact2->shouldReceive('getId')->andReturns('2');
        $artifact3 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact3->shouldReceive('getId')->andReturns('3');
        $artifact4 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact4->shouldReceive('getId')->andReturns('4');

        $af = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->tracker->shouldReceive('getTrackerArtifactFactory')->andReturns($af);
        $af->shouldReceive('getArtifactById')->with('1')->andReturns($artifact1);
        $af->shouldReceive('getArtifactById')->with('2')->andReturns($artifact2);
        $af->shouldReceive('getArtifactById')->with('3')->andReturns($artifact3);
        $af->shouldReceive('getArtifactById')->with('4')->andReturns($artifact4);

        $this->tracker->shouldReceive('aidExists')->andReturns(true);
        $this->assertFalse($this->tracker->hasUnknownAid($header, $lines));
    }

    public function testHasUnknownAidUpdateModeError()
    {
        $header = array('aid','summary', 'details');
        $lines = array(
                    array('1','summary 1', 'details 1'),
                    array('2','summary 2', 'details 2'),
                    array('3','summary 3', 'details 3'),
                    array('4','summary 4', 'details 4'),
                 );

        $artifact1 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact1->shouldReceive('getId')->andReturns('1');
        $artifact2 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact2->shouldReceive('getId')->andReturns('2');
        $artifact3 = \Mockery::spy(\Tracker_Artifact::class);
        $artifact3->shouldReceive('getId')->andReturns('3');

        $af = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->tracker->shouldReceive('getTrackerArtifactFactory')->andReturns($af);
        $af->shouldReceive('getArtifactById')->with('1')->andReturns($artifact1);
        $af->shouldReceive('getArtifactById')->with('2')->andReturns($artifact2);
        $af->shouldReceive('getArtifactById')->with('3')->andReturns($artifact3);
        $af->shouldReceive('getArtifactById')->with('4')->andReturns(null);

        $this->tracker->shouldReceive('aidExists')->with('1')->andReturns(true);
        $this->tracker->shouldReceive('aidExists')->with('2')->andReturns(true);
        $this->tracker->shouldReceive('aidExists')->with('3')->andReturns(true);
        $this->tracker->shouldReceive('aidExists')->with('4')->andReturns(false);

        $this->assertTrue($this->tracker->hasUnknownAid($header, $lines));
    }

    public function testIsValidCSVWrongSeparator()
    {
        $lines = array(
                    array('aid;summary;details'),
                    array('1;summary 1;details 1'),
                    array('2;summary 2;details 2'),
                    array('3;summary 3;details 3'),
                    array('4;summary 4;details 4'),
                 );
        $separator = ',';

        $tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker->shouldReceive('hasBlockingError')->andReturns(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', Mockery::any(), Mockery::any())->once();    // expected warning about wrong separator
        $tracker->isValidCSV($lines, $separator);
    }

    public function testIsValidCSVGoodSeparator()
    {
        $lines = array(
                    array('aid', 'summary', 'details'),
                    array('1', 'summary 1', 'details 1'),
                    array('2', 'summary 2', 'details 2'),
                    array('3', 'summary 3', 'details 3'),
                    array('4', 'summary 4', 'details 4'),
                 );
        $separator = ',';

        stub($this->workflow_factory)->getGlobalRulesManager()->returns(\Mockery::spy(\Tracker_RulesManager::class));
        $tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker->shouldReceive('hasBlockingError')->andReturns(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->never();
        $tracker->isValidCSV($lines, $separator);
    }

    public function testCreateFormElementDispatchesToOrdinaryFieldCreation()
    {
        $data = array('type' => 'string');

        list($tracker, $factory, $sharedFactory, $user) = $this->GivenATrackerAndItsFactories();
        $factory->shouldReceive('createFormElement')->with($tracker, $data['type'], $data, false, false)->once();
        $sharedFactory->shouldReceive('createFormElement')->never();

        $tracker->createFormElement($data['type'], $data, $user);
    }

    public function testCreateFormElementDispatchesToSharedField()
    {
        $data = array('type' => 'shared');

        list($tracker, $factory, $sharedFactory, $user) = $this->GivenATrackerAndItsFactories();
        $factory->shouldReceive('createFormElement')->never();
        $sharedFactory->shouldReceive('createFormElement')->with($tracker, $data, $user, false, false)->once();

        $tracker->createFormElement($data['type'], $data, $user);
    }

    private function GivenATrackerAndItsFactories()
    {
        $tracker = new Tracker(null, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $tracker->setFormElementFactory($factory);
        $sharedFactory = \Mockery::spy(\Tracker_SharedFormElementFactory::class);
        $tracker->setSharedFormElementFactory($sharedFactory);
        $user = \Mockery::spy(\PFUser::class);
        return array($tracker, $factory, $sharedFactory, $user);
    }
}

class Tracker_ExportToXmlTest extends TuleapTestCase
{

    private $tracker;
    private $formelement_factory;
    private $workflow_factory;
    private $hierarchy;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->tracker)->getId()->returns(110);
        stub($this->tracker)->getColor()->returns(TrackerColor::default());
        stub($this->tracker)->getUserManager()->returns(\Mockery::spy(\UserManager::class));
        stub($this->tracker)->getProject()->returns(\Mockery::spy(\Project::class));

        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($this->tracker)->getFormElementFactory()->returns($this->formelement_factory);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(\Mockery::spy(\Tracker_RulesManager::class));
        stub($this->tracker)->getWorkflowFactory()->returns($this->workflow_factory);

        $this->hierarchy = new Tracker_Hierarchy();
        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getHierarchy()->returns($this->hierarchy);
        stub($this->tracker)->getHierarchyFactory()->returns($hierarchy_factory);

        $tcrm = \Mockery::spy(\Tracker_CannedResponseManager::class);
        stub($this->tracker)->getCannedResponseManager()->returns($tcrm);

        $canned_response_factory = \Mockery::spy(\Tracker_CannedResponseFactory::class);
        stub($this->tracker)->getCannedResponseFactory()->returns($canned_response_factory);

        $tsm = \Mockery::spy(\Tracker_SemanticManager::class);
        stub($this->tracker)->getTrackerSemanticManager()->returns($tsm);

        $report_factory = \Mockery::spy(\Tracker_ReportFactory::class);
        stub($this->tracker)->getReportFactory()->returns($report_factory);

        $webhook_xml_exporter = \Mockery::mock(\Tuleap\Tracker\Webhook\WebhookXMLExporter::class);
        $webhook_xml_exporter->shouldReceive('exportTrackerWebhooksInXML')->once();
        stub($this->tracker)->getWebhookXMLExporter()->returns($webhook_xml_exporter);
    }

    public function testPermissionsExport()
    {
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
            \Mockery::spy(\Tracker_RulesManager::class)
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

    public function itExportsTheTrackerID()
    {
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(\Mockery::spy(\Tracker_RulesManager::class));
        stub($this->tracker)->getProjectUgroups()->returns([]);
        stub($this->tracker)->getPermissionsByUgroupId()->returns([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEqual((string)$attributes['id'], 'T110');
    }

    public function itExportsNoParentIfNotInAHierarchy()
    {
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(\Mockery::spy(\Tracker_RulesManager::class));
        stub($this->tracker)->getProjectUgroups()->returns([]);
        stub($this->tracker)->getPermissionsByUgroupId()->returns([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEqual((string)$attributes['parent_id'], "0");
    }

    public function itExportsTheParentId()
    {
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(\Mockery::spy(\Tracker_RulesManager::class));
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->tracker)->getProjectUgroups()->returns([]);
        stub($this->tracker)->getPermissionsByUgroupId()->returns([]);

        $this->hierarchy->addRelationship(9001, 110);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEqual((string)$attributes['parent_id'], "T9001");
    }

    public function itExportsTheTrackerColor()
    {
        stub($this->formelement_factory)->getUsedFormElementForTracker()->returns(array());
        stub($this->workflow_factory)->getGlobalRulesManager()->returns(\Mockery::spy(\Tracker_RulesManager::class));
        stub($this->tracker)->getProjectUgroups()->returns([]);
        stub($this->tracker)->getPermissionsByUgroupId()->returns([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $color = $xml->color;
        $this->assertEqual((string)$color, TrackerColor::default()->getName());
    }
}

class Tracker_WorkflowTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->tracker_id = 12;
        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->setId($this->tracker_id);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        stub($this->tracker)->getWorkflowFactory()->returns($this->workflow_factory);
    }

    public function itHasADefaultWorkflow()
    {
        $workflow = aWorkflow()->withTrackerId($this->tracker_id)->build();
        stub($this->workflow_factory)->getWorkflowByTrackerId()->returns(false);
        stub($this->workflow_factory)->getWorkflowWithoutTransition()->returns($workflow);
        $this->assertEqual($this->tracker->getWorkflow(), $workflow);
    }

    public function itAlwaysHaveTheSameDefaultWorkflow()
    {
        stub($this->workflow_factory)->getWorkflowByTrackerId()->returns(false);
        stub($this->workflow_factory)->getWorkflowWithoutTransition()->returns(aWorkflow()->withTrackerId(12)->build());
        stub($this->workflow_factory)->getWorkflowWithoutTransition()->returns(aWorkflow()->withTrackerId(33)->build());
        $this->assertEqual($this->tracker->getWorkflow(), $this->tracker->getWorkflow());
    }

    public function itHasAWorkflowFromTheFactoryWhenThereAreTransitions()
    {
        $workflow = aWorkflow()->withTrackerId($this->tracker_id)->build();
        stub($this->workflow_factory)->getWorkflowByTrackerId($this->tracker_id)->returns($workflow);
        $this->assertEqual($this->tracker->getWorkflow(), $workflow);
    }
}


class Tracker_getParentTest extends TuleapTestCase
{

    private $tracker;
    private $tracker_factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->tracker)->getTrackerFactory()->returns($this->tracker_factory);
    }

    public function itReturnsNullWhenItHasNoParentFromAccessor()
    {
        $tracker = aTracker()->build();
        $tracker->setParent(null);
        $this->assertEqual($tracker->getParent(), null);
    }

    public function itReturnsParentWhenParentWasSetByAccessor()
    {
        $parent  = aTracker()->build();
        $tracker = aTracker()->build();
        $tracker->setParent($parent);
        $this->assertEqual($tracker->getParent(), $parent);
    }

    public function itReturnsNullWhenItHasNoParentFromDb()
    {
        stub($this->tracker)->getParentId()->returns(null);
        $this->assertEqual($this->tracker->getParent(), null);
    }

    public function itReturnsNullWhenParentNotFoundInDb()
    {
        stub($this->tracker_factory)->getTrackerById(15)->returns(null);
        stub($this->tracker)->getParentId()->returns(15);
        $this->assertEqual($this->tracker->getParent(), null);
    }

    public function itReturnsParentWhenFetchedFromDb()
    {
        $parent  = aTracker()->build();
        stub($this->tracker_factory)->getTrackerById(15)->returns($parent);
        stub($this->tracker)->getParentId()->returns(15);
        $this->assertEqual($this->tracker->getParent(), $parent);
    }

    public function itDoesntFetchParentTwiceWhenThereIsParent()
    {
        $parent  = aTracker()->build();
        expect($this->tracker_factory)->getTrackerById(15)->once();
        stub($this->tracker_factory)->getTrackerById(15)->returns($parent);
        stub($this->tracker)->getParentId()->returns(15);

        $this->tracker->getParent();
        $this->tracker->getParent();
    }

    public function itDoesntFetchParentTwiceWhenOrphan()
    {
        expect($this->tracker_factory)->getTrackerById(15)->once();
        stub($this->tracker_factory)->getTrackerById(15)->returns(null);
        stub($this->tracker)->getParentId()->returns(15);

        $this->tracker->getParent();
        $this->tracker->getParent();
    }
}
