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
        $xmlMapping['F' . $this->getId()] = $this->getId();
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
