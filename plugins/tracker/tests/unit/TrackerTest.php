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

declare(strict_types=1);

use Tuleap\Tracker\TrackerColor;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class TrackerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    private $initial_global_ugroups;

    /**
     * @var \Mockery\Mock
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|WorkflowFactory
     */
    private $workflow_factory;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $project->shouldReceive('isPublic')->andReturns(true);
        $project->shouldReceive('isActive')->andReturns(true);

        $project_private = \Mockery::spy(\Project::class);
        $project_private->shouldReceive('getID')->andReturns(102);
        $project_private->shouldReceive('isPublic')->andReturns(false);
        $project_private->shouldReceive('isActive')->andReturns(true);

        $this->tracker  = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker1       = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker2 = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker_manager = \Mockery::spy(\TrackerManager::class);

        $this->tracker->shouldReceive('getTrackerManager')->andReturns($tracker_manager);
        $tracker1->shouldReceive('getTrackerManager')->andReturns($tracker_manager);
        $tracker2->shouldReceive('getTrackerManager')->andReturns($tracker_manager);

        $tracker_manager->shouldReceive('userCanAdminAllProjectTrackers')->andReturns(false);

        $tf = \Mockery::spy(\TrackerFactory::class);
        $this->tracker->shouldReceive('getTrackerFactory')->andReturns($tf);
        $tracker1->shouldReceive('getTrackerFactory')->andReturns($tf);
        $tracker2->shouldReceive('getTrackerFactory')->andReturns($tf);
        $tsm = \Mockery::spy(\Tracker_SemanticManager::class);
        $this->tracker->shouldReceive('getTrackerSemanticManager')->andReturns($tsm);
        $tracker1->shouldReceive('getTrackerSemanticManager')->andReturns($tsm);
        $tracker2->shouldReceive('getTrackerSemanticManager')->andReturns($tsm);
        $tnm = \Mockery::spy(\Tracker_NotificationsManager::class);
        $this->tracker->shouldReceive('getNotificationsManager')->andReturns($tnm);
        $tracker1->shouldReceive('getNotificationsManager')->andReturns($tnm);
        $tracker2->shouldReceive('getNotificationsManager')->andReturns($tnm);
        $trr = \Mockery::spy(\Tracker_DateReminderManager::class);
        $this->tracker->shouldReceive('getDateReminderManager')->andReturns($trr);
        $tracker1->shouldReceive('getDateReminderManager')->andReturns($trr);
        $tracker2->shouldReceive('getDateReminderManager')->andReturns($trr);
        $tcrm = \Mockery::spy(\Tracker_CannedResponseManager::class);
        $this->tracker->shouldReceive('getCannedResponseManager')->andReturns($tcrm);
        $tracker1->shouldReceive('getCannedResponseManager')->andReturns($tcrm);
        $tracker2->shouldReceive('getCannedResponseManager')->andReturns($tcrm);
        $wm = \Mockery::spy(\WorkflowManager::class);
        $this->tracker->shouldReceive('getWorkflowManager')->andReturns($wm);
        $tracker1->shouldReceive('getWorkflowManager')->andReturns($wm);
        $tracker2->shouldReceive('getWorkflowManager')->andReturns($wm);
        $group_id = 999;
        $this->tracker->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker->shouldReceive('getId')->andReturns(110);
        $this->tracker->shouldReceive('getColor')->andReturns(TrackerColor::default());
        $tracker1->shouldReceive('getGroupId')->andReturns($group_id);
        $tracker1->shouldReceive('getId')->andReturns(111);
        $tracker2->shouldReceive('getGroupId')->andReturns($group_id);
        $tracker2->shouldReceive('getId')->andReturns(112);

        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturns([
            1 => ['PERM_1'],
            3 => ['PERM_2'],
            5 => ['PERM_3'],
            115 => ['PERM_3'],
        ]);
        $tracker1->shouldReceive('getPermissionsByUgroupId')->andReturns([
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ]);
        $tracker2->shouldReceive('getPermissionsByUgroupId')->andReturns([
            1002 => [ 102 => 'PLUGIN_TRACKER_ADMIN'],
        ]);

        $site_admin_user = \Mockery::spy(\PFUser::class);
        $site_admin_user->shouldReceive('getId')->andReturns(1);
        $site_admin_user->shouldReceive('isMember')->andReturns(false);
        $site_admin_user->shouldReceive('isSuperUser')->andReturns(true);
        $site_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $site_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $site_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $project_admin_user = \Mockery::spy(\PFUser::class);
        $project_admin_user->shouldReceive('getId')->andReturns(123);
        $project_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(true);
        $project_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $project_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $project_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $project_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $project_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $all_trackers_admin_user = \Mockery::spy(\PFUser::class);
        $all_trackers_admin_user->shouldReceive('getId')->andReturns(222);
        $all_trackers_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $all_trackers_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $all_trackers_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $all_trackers_admin_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $all_trackers_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(true); //1001 = ugroup who has ADMIN perm on tracker
        $all_trackers_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(true); //1002 = ugroup who has ADMIN perm on tracker
        $all_trackers_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $tracker1_admin_user = \Mockery::spy(\PFUser::class);
        $tracker1_admin_user->shouldReceive('getId')->andReturns(333);
        $tracker1_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $tracker1_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $tracker1_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $tracker1_admin_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $tracker1_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(true);
        $tracker1_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $tracker1_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $tracker2_admin_user = \Mockery::spy(\PFUser::class);
        $tracker2_admin_user->shouldReceive('getId')->andReturns(444);
        $tracker2_admin_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $tracker2_admin_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $tracker2_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $tracker2_admin_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $tracker2_admin_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $tracker2_admin_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(true);
        $tracker2_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $project_member_user = \Mockery::spy(\PFUser::class);
        $project_member_user->shouldReceive('getId')->andReturns(555);
        $project_member_user->shouldReceive('isMember')->with($group_id, 'A')->andReturns(false);
        $project_member_user->shouldReceive('isMember')->with(102)->andReturns(false);
        $project_member_user->shouldReceive('isSuperUser')->andReturns(false);
        $project_member_user->shouldReceive('isMember')->with($group_id, 0)->andReturns(true);
        $project_member_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $project_member_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $project_member_user->shouldReceive('isTrackerAdmin')->andReturns(false);
        $project_member_user->shouldReceive('isLoggedIn')->andReturns(true);

        $registered_user = \Mockery::spy(\PFUser::class);
        $registered_user->shouldReceive('getId')->andReturns(777);
        $registered_user->shouldReceive('isMember')->andReturns(false);
        $registered_user->shouldReceive('isSuperUser')->andReturns(false);
        $registered_user->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $registered_user->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $registered_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        $this->tracker->shouldReceive('getWorkflowFactory')->andReturns($this->workflow_factory);

        $formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->tracker->shouldReceive('getFormElementFactory')->andReturns($formelement_factory);

        $report_factory = \Mockery::spy(\Tracker_ReportFactory::class);
        $this->tracker->shouldReceive('getReportFactory')->andReturns($report_factory);

        $canned_response_factory = \Mockery::spy(\Tracker_CannedResponseFactory::class);
        $this->tracker->shouldReceive('getCannedResponseFactory')->andReturns($canned_response_factory);

        $permission_controller = \Mockery::spy(\Tracker_Permission_PermissionController::class);
        $this->tracker->shouldReceive('getPermissionController')->andReturns($permission_controller);

        $permission_controller1 = \Mockery::spy(\Tracker_Permission_PermissionController::class);
        $tracker1->shouldReceive('getPermissionController')->andReturns($permission_controller1);

        $permission_controller2 = \Mockery::spy(\Tracker_Permission_PermissionController::class);
        $tracker2->shouldReceive('getPermissionController')->andReturns($permission_controller2);

        $hierarchy         = new Tracker_Hierarchy();
        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getHierarchy')->andReturns($hierarchy);
        $this->tracker->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        WorkflowFactory::setInstance($this->workflow_factory);

        $user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($user_manager);

        $this->initial_global_ugroups = $GLOBALS['UGROUPS'];
        $GLOBALS['UGROUPS'] = [
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        ];
    }

    protected function tearDown(): void
    {
        $GLOBALS['UGROUPS'] = $this->initial_global_ugroups;
        WorkflowFactory::clearInstance();
        UserManager::clearInstance();
    }

    public function testHasUnknownAidCreateMode(): void
    {
        $header = ['summary', 'details'];
        $lines = [
                    ['summary 1', 'details 1'],
                    ['summary 2', 'details 2'],
                    ['summary 3', 'details 3'],
                    ['summary 4', 'details 4'],
                 ];

        $this->assertFalse($this->tracker->hasUnknownAid($header, $lines));
    }

    public function testHasUnknownAidUpdateModeNoError(): void
    {
        $header = ['aid', 'summary', 'details'];
        $lines = [
                    ['1','summary 1', 'details 1'],
                    ['2','summary 2', 'details 2'],
                    ['3','summary 3', 'details 3'],
                    ['4','summary 4', 'details 4'],
                 ];

        $artifact1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact1->shouldReceive('getId')->andReturns('1');
        $artifact2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact2->shouldReceive('getId')->andReturns('2');
        $artifact3 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact3->shouldReceive('getId')->andReturns('3');
        $artifact4 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
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

    public function testHasUnknownAidUpdateModeError(): void
    {
        $header = ['aid', 'summary', 'details'];
        $lines = [
                    ['1','summary 1', 'details 1'],
                    ['2','summary 2', 'details 2'],
                    ['3','summary 3', 'details 3'],
                    ['4','summary 4', 'details 4'],
                 ];

        $artifact1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact1->shouldReceive('getId')->andReturns('1');
        $artifact2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact2->shouldReceive('getId')->andReturns('2');
        $artifact3 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
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

    public function testIsValidCSVWrongSeparator(): void
    {
        $lines = [
                    ['aid;summary;details'],
                    ['1;summary 1;details 1'],
                    ['2;summary 2;details 2'],
                    ['3;summary 3;details 3'],
                    ['4;summary 4;details 4'],
                 ];
        $separator = ',';

        $tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker->shouldReceive('hasBlockingError')->andReturns(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', Mockery::any(), Mockery::any())->once();    // expected warning about wrong separator
        $tracker->isValidCSV($lines, $separator);
    }

    public function testIsValidCSVGoodSeparator(): void
    {
        $lines = [
                    ['aid', 'summary', 'details'],
                    ['1', 'summary 1', 'details 1'],
                    ['2', 'summary 2', 'details 2'],
                    ['3', 'summary 3', 'details 3'],
                    ['4', 'summary 4', 'details 4'],
                 ];
        $separator = ',';

        $this->workflow_factory->shouldReceive('getGlobalRulesManager')->andReturns(\Mockery::spy(\Tracker_RulesManager::class));
        $tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker->shouldReceive('hasBlockingError')->andReturns(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->never();
        $tracker->isValidCSV($lines, $separator);
    }

    public function testCreateFormElementDispatchesToOrdinaryFieldCreation(): void
    {
        $data = ['type' => 'string'];

        [$tracker, $factory, $sharedFactory, $user] = $this->givenATrackerAndItsFactories();
        $factory->shouldReceive('createFormElement')->with($tracker, $data['type'], $data, false, false)->once();
        $sharedFactory->shouldReceive('createFormElement')->never();

        $tracker->createFormElement($data['type'], $data, $user);
    }

    public function testCreateFormElementDispatchesToSharedField(): void
    {
        $data = ['type' => 'shared'];

        [$tracker, $factory, $sharedFactory, $user] = $this->givenATrackerAndItsFactories();
        $factory->shouldReceive('createFormElement')->never();
        $sharedFactory->shouldReceive('createFormElement')->with($tracker, $data, $user, false, false)->once();

        $tracker->createFormElement($data['type'], $data, $user);
    }

    private function givenATrackerAndItsFactories(): array
    {
        $tracker = new Tracker(null, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $tracker->setFormElementFactory($factory);
        $sharedFactory = \Mockery::spy(\Tracker_SharedFormElementFactory::class);
        $tracker->setSharedFormElementFactory($sharedFactory);
        $user = \Mockery::spy(\PFUser::class);
        return [$tracker, $factory, $sharedFactory, $user];
    }

    private function setUpTrackerWorkflowTest(): void
    {
        $this->tracker_id = 12;
        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->setId($this->tracker_id);

        $this->workflow_factory = \Mockery::spy(\WorkflowFactory::class);
        $this->tracker->shouldReceive('getWorkflowFactory')->andReturns($this->workflow_factory);
    }

    public function testItHasADefaultWorkflow(): void
    {
        $this->setUpTrackerWorkflowTest();
        $workflow = Mockery::mock(WorkflowWithoutTransition::class)->shouldReceive('getId')->andReturn($this->tracker_id)->getMock();
        $this->workflow_factory->shouldReceive('getWorkflowByTrackerId')->andReturns(false);
        $this->workflow_factory->shouldReceive('getWorkflowWithoutTransition')->andReturns($workflow);
        $this->assertEquals($workflow, $this->tracker->getWorkflow());
    }

    public function testItAlwaysHaveTheSameDefaultWorkflow(): void
    {
        $this->setUpTrackerWorkflowTest();
        $this->workflow_factory->shouldReceive('getWorkflowByTrackerId')->andReturns(false);
        $this->workflow_factory->shouldReceive('getWorkflowWithoutTransition')->andReturns(
            Mockery::mock(WorkflowWithoutTransition::class)->shouldReceive('getId')->andReturn(12)->getMock()
        );
        $this->workflow_factory->shouldReceive('getWorkflowWithoutTransition')->andReturns(
            Mockery::mock(WorkflowWithoutTransition::class)->shouldReceive('getId')->andReturn(33)->getMock()
        );
        $this->assertEquals($this->tracker->getWorkflow(), $this->tracker->getWorkflow());
    }

    public function testItHasAWorkflowFromTheFactoryWhenThereAreTransitions(): void
    {
        $this->setUpTrackerWorkflowTest();
        $workflow = Mockery::mock(Workflow::class)->shouldReceive('getId')->andReturn($this->tracker_id)->getMock();
        $this->workflow_factory->shouldReceive('getWorkflowByTrackerId')->with($this->tracker_id)->andReturns($workflow);
        $this->assertEquals($workflow, $this->tracker->getWorkflow());
    }

    private function setUpGetParentTest(): void
    {
        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->shouldReceive('getTrackerFactory')->andReturns($this->tracker_factory);
    }

    public function testItReturnsNullWhenItHasNoParentFromAccessor(): void
    {
        $this->setUpGetParentTest();
        $this->tracker->setParent(null);
        $this->assertEquals(null, $this->tracker->getParent());
    }

    public function testItReturnsParentWhenParentWasSetByAccessor(): void
    {
        $this->setUpGetParentTest();
        $parent = Mockery::mock(Tracker::class);
        $this->tracker->setParent($parent);
        $this->assertEquals($parent, $this->tracker->getParent());
    }

    public function testItReturnsNullWhenItHasNoParentFromDb(): void
    {
        $this->setUpGetParentTest();
        $this->tracker->shouldReceive('getParentId')->andReturns(null);
        $this->assertEquals(null, $this->tracker->getParent());
    }

    public function testItReturnsNullWhenParentNotFoundInDb(): void
    {
        $this->setUpGetParentTest();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(15)->andReturns(null);
        $this->tracker->shouldReceive('getParentId')->andReturns(15);
        $this->assertEquals(null, $this->tracker->getParent());
    }

    public function testItReturnsParentWhenFetchedFromDb(): void
    {
        $this->setUpGetParentTest();
        $parent = Mockery::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(15)->andReturns($parent);
        $this->tracker->shouldReceive('getParentId')->andReturns(15);
        $this->assertEquals($parent, $this->tracker->getParent());
    }

    public function testItDoesntFetchParentTwiceWhenThereIsParent(): void
    {
        $this->setUpGetParentTest();
        $parent = Mockery::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(15)->once();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(15)->andReturns($parent);
        $this->tracker->shouldReceive('getParentId')->andReturns(15);

        $this->tracker->getParent();
        $this->tracker->getParent();
    }

    public function testItDoesntFetchParentTwiceWhenOrphan(): void
    {
        $this->setUpGetParentTest();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(15)->once();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(15)->andReturns(null);
        $this->tracker->shouldReceive('getParentId')->andReturns(15);

        $this->tracker->getParent();
        $this->tracker->getParent();
    }
}
