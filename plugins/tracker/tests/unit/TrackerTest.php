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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Color\ItemColor;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Tuleap\GlobalResponseMock;

    private array $initial_global_ugroups;

    private Tracker&MockObject $tracker;
    private WorkflowFactory&MockObject $workflow_factory;
    private int $tracker_id = 12;
    private TrackerFactory&MockObject $tracker_factory;

    protected function setUp(): void
    {
        $methods       = [
            'getTrackerFactory',
            'getTrackerSemanticManager',
            'getNotificationsManager',
            'getCannedResponseManager',
            'getWorkflowManager',
            'getWorkflowFactory',
            'getGroupId',
            'getId',
            'getColor',
            'getPermissionsByUgroupId',
            'getFormElementFactory',
            'getReportFactory',
            'getCannedResponseFactory',
            'getPermissionController',
            'getHierarchyFactory',
            'getTrackerArtifactFactory',
            'aidExists',
            'hasBlockingError',
            'getParentId',
        ];
        $this->tracker = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, $methods);
        $tracker1      = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, $methods);
        $tracker2      = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, $methods);

        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
        $this->tracker->method('getTrackerFactory')->willReturn($this->tracker_factory);
        $tracker1->method('getTrackerFactory')->willReturn($this->tracker_factory);
        $tracker2->method('getTrackerFactory')->willReturn($this->tracker_factory);
        $tsm = $this->createMock(\Tuleap\Tracker\Semantic\TrackerSemanticManager::class);
        $this->tracker->method('getTrackerSemanticManager')->willReturn($tsm);
        $tracker1->method('getTrackerSemanticManager')->willReturn($tsm);
        $tracker2->method('getTrackerSemanticManager')->willReturn($tsm);
        $tnm = $this->createMock(\Tracker_NotificationsManager::class);
        $this->tracker->method('getNotificationsManager')->willReturn($tnm);
        $tracker1->method('getNotificationsManager')->willReturn($tnm);
        $tracker2->method('getNotificationsManager')->willReturn($tnm);
        $tcrm = $this->createMock(\Tracker_CannedResponseManager::class);
        $this->tracker->method('getCannedResponseManager')->willReturn($tcrm);
        $tracker1->method('getCannedResponseManager')->willReturn($tcrm);
        $tracker2->method('getCannedResponseManager')->willReturn($tcrm);
        $wm = $this->createMock(\WorkflowManager::class);
        $this->tracker->method('getWorkflowManager')->willReturn($wm);
        $tracker1->method('getWorkflowManager')->willReturn($wm);
        $tracker2->method('getWorkflowManager')->willReturn($wm);
        $group_id = 999;
        $this->tracker->method('getGroupId')->willReturn($group_id);
        $this->tracker->method('getId')->willReturn(110);
        $this->tracker->method('getColor')->willReturn(ItemColor::default());
        $tracker1->method('getGroupId')->willReturn($group_id);
        $tracker1->method('getId')->willReturn(111);
        $tracker2->method('getGroupId')->willReturn($group_id);
        $tracker2->method('getId')->willReturn(112);

        $this->tracker->method('getPermissionsByUgroupId')->willReturn([
            1 => ['PERM_1'],
            3 => ['PERM_2'],
            5 => ['PERM_3'],
            115 => ['PERM_3'],
        ]);
        $tracker1->method('getPermissionsByUgroupId')->willReturn([
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ]);
        $tracker2->method('getPermissionsByUgroupId')->willReturn([
            1002 => [ 102 => 'PLUGIN_TRACKER_ADMIN'],
        ]);

        $site_admin_user = $this->createMock(\PFUser::class);
        $site_admin_user->method('getId')->willReturn(1);
        $site_admin_user->method('isMember')->willReturn(false);
        $site_admin_user->method('isSuperUser')->willReturn(true);
        $site_admin_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(false);
        $site_admin_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(false);

        $project_admin_user = $this->createMock(\PFUser::class);
        $project_admin_user->method('getId')->willReturn(123);
        $project_admin_user->method('isMember')->with($group_id, 'A')->willReturn(true);
        $project_admin_user->method('isMember')->with(102)->willReturn(false);
        $project_admin_user->method('isSuperUser')->willReturn(false);
        $project_admin_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(false);
        $project_admin_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(false);

        $all_trackers_admin_user = $this->createMock(\PFUser::class);
        $all_trackers_admin_user->method('getId')->willReturn(222);
        $all_trackers_admin_user->method('isMember')->with($group_id, 'A')->willReturn(false);
        $all_trackers_admin_user->method('isMember')->with(102)->willReturn(false);
        $all_trackers_admin_user->method('isSuperUser')->willReturn(false);
        $all_trackers_admin_user->method('isMember')->with($group_id, 0)->willReturn(true);
        $all_trackers_admin_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(true); //1001 = ugroup who has ADMIN perm on tracker
        $all_trackers_admin_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(true); //1002 = ugroup who has ADMIN perm on tracker

        $tracker1_admin_user = $this->createMock(\PFUser::class);
        $tracker1_admin_user->method('getId')->willReturn(333);
        $tracker1_admin_user->method('isMember')->with($group_id, 'A')->willReturn(false);
        $tracker1_admin_user->method('isMember')->with(102)->willReturn(false);
        $tracker1_admin_user->method('isSuperUser')->willReturn(false);
        $tracker1_admin_user->method('isMember')->with($group_id, 0)->willReturn(true);
        $tracker1_admin_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(true);
        $tracker1_admin_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(false);

        $tracker2_admin_user = $this->createMock(\PFUser::class);
        $tracker2_admin_user->method('getId')->willReturn(444);
        $tracker2_admin_user->method('isMember')->with($group_id, 'A')->willReturn(false);
        $tracker2_admin_user->method('isMember')->with(102)->willReturn(false);
        $tracker2_admin_user->method('isSuperUser')->willReturn(false);
        $tracker2_admin_user->method('isMember')->with($group_id, 0)->willReturn(true);
        $tracker2_admin_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(false);
        $tracker2_admin_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(true);

        $project_member_user = $this->createMock(\PFUser::class);
        $project_member_user->method('getId')->willReturn(555);
        $project_member_user->method('isMember')->with($group_id, 'A')->willReturn(false);
        $project_member_user->method('isMember')->with(102)->willReturn(false);
        $project_member_user->method('isSuperUser')->willReturn(false);
        $project_member_user->method('isMember')->with($group_id, 0)->willReturn(true);
        $project_member_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(false);
        $project_member_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(false);
        $project_member_user->method('isTrackerAdmin')->willReturn(false);

        $registered_user = $this->createMock(\PFUser::class);
        $registered_user->method('getId')->willReturn(777);
        $registered_user->method('isMember')->willReturn(false);
        $registered_user->method('isSuperUser')->willReturn(false);
        $registered_user->method('isMemberOfUGroup')->with(1001, $this->anything())->willReturn(false);
        $registered_user->method('isMemberOfUGroup')->with(1002, $this->anything())->willReturn(false);

        $this->workflow_factory = $this->createMock(\WorkflowFactory::class);
        $this->tracker->method('getWorkflowFactory')->willReturn($this->workflow_factory);

        $formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->tracker->method('getFormElementFactory')->willReturn($formelement_factory);

        $report_factory = $this->createMock(\Tracker_ReportFactory::class);
        $this->tracker->method('getReportFactory')->willReturn($report_factory);

        $canned_response_factory = $this->createMock(\Tracker_CannedResponseFactory::class);
        $this->tracker->method('getCannedResponseFactory')->willReturn($canned_response_factory);

        $permission_controller = $this->createMock(\Tracker_Permission_PermissionController::class);
        $this->tracker->method('getPermissionController')->willReturn($permission_controller);

        $permission_controller1 = $this->createMock(\Tracker_Permission_PermissionController::class);
        $tracker1->method('getPermissionController')->willReturn($permission_controller1);

        $permission_controller2 = $this->createMock(\Tracker_Permission_PermissionController::class);
        $tracker2->method('getPermissionController')->willReturn($permission_controller2);

        $hierarchy         = new Tracker_Hierarchy();
        $hierarchy_factory = $this->createMock(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);
        $this->tracker->method('getHierarchyFactory')->willReturn($hierarchy_factory);

        WorkflowFactory::setInstance($this->workflow_factory);

        $user_manager = $this->createMock(\UserManager::class);
        UserManager::setInstance($user_manager);

        $this->initial_global_ugroups = $GLOBALS['UGROUPS'];
        $GLOBALS['UGROUPS']           = [
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
        $lines  = [
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
        $lines  = [
            ['1','summary 1', 'details 1'],
            ['2','summary 2', 'details 2'],
            ['3','summary 3', 'details 3'],
            ['4','summary 4', 'details 4'],
        ];

        $artifact1 = ArtifactTestBuilder::anArtifact(1)->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(3)->build();
        $artifact4 = ArtifactTestBuilder::anArtifact(4)->build();

        $af = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->tracker->method('getTrackerArtifactFactory')->willReturn($af);
        $af->method('getArtifactById')->willReturnCallback(
            static fn (int $id) => match ($id) {
                $artifact1->getId() => $artifact1,
                $artifact2->getId() => $artifact2,
                $artifact3->getId() => $artifact3,
                $artifact4->getId() => $artifact4,
                default => null,
            }
        );

        $this->tracker->method('aidExists')->willReturn(true);
        $this->assertFalse($this->tracker->hasUnknownAid($header, $lines));
    }

    public function testHasUnknownAidUpdateModeError(): void
    {
        $header = ['aid', 'summary', 'details'];
        $lines  = [
            ['1','summary 1', 'details 1'],
            ['2','summary 2', 'details 2'],
            ['3','summary 3', 'details 3'],
            ['4','summary 4', 'details 4'],
        ];

        $artifact1 = ArtifactTestBuilder::anArtifact(1)->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(3)->build();

        $af = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->tracker->method('getTrackerArtifactFactory')->willReturn($af);
        $af->method('getArtifactById')->willReturnCallback(
            static fn (int $id) => match ($id) {
                $artifact1->getId() => $artifact1,
                $artifact2->getId() => $artifact2,
                $artifact3->getId() => $artifact3,
                default => null,
            }
        );

        $this->tracker->method('aidExists')->willReturnCallback(
            static fn (string $id) => match ((int) $id) {
                1, 2, 3 => true,
                4 => false,
            }
        );

        $this->assertTrue($this->tracker->hasUnknownAid($header, $lines));
    }

    public function testIsValidCSVWrongSeparator(): void
    {
        $lines     = [
            ['aid;summary;details'],
            ['1;summary 1;details 1'],
            ['2;summary 2;details 2'],
            ['3;summary 3;details 3'],
            ['4;summary 4;details 4'],
        ];
        $separator = ',';

        $this->tracker->method('hasBlockingError')->willReturn(false);

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('warning');    // expected warning about wrong separator
        $this->tracker->isValidCSV($lines, $separator);
    }

    public function testIsValidCSVGoodSeparator(): void
    {
        $lines     = [
            ['aid', 'summary', 'details'],
            ['1', 'summary 1', 'details 1'],
            ['2', 'summary 2', 'details 2'],
            ['3', 'summary 3', 'details 3'],
            ['4', 'summary 4', 'details 4'],
        ];
        $separator = ',';

        $this->tracker->method('hasBlockingError')->willReturn(false);

        $GLOBALS['Response']->expects($this->never())->method('addFeedback');
        $this->tracker->isValidCSV($lines, $separator);
    }

    public function testCreateFormElementDispatchesToOrdinaryFieldCreation(): void
    {
        $data = ['type' => 'string'];

        [$tracker, $factory, $shared_factory, $user] = $this->givenATrackerAndItsFactories();
        $factory->expects($this->once())->method('createFormElement')->with($tracker, $data['type'], $data, false, false);
        $shared_factory->expects($this->never())->method('createFormElement');

        $tracker->createFormElement($data['type'], $data, $user);
    }

    public function testCreateFormElementDispatchesToSharedField(): void
    {
        $data = ['type' => 'shared'];

        [$tracker, $factory, $shared_factory, $user] = $this->givenATrackerAndItsFactories();
        $factory->expects($this->never())->method('createFormElement');
        $shared_factory->expects($this->once())->method('createFormElement')->with($tracker, $data, $user, false, false);

        $tracker->createFormElement($data['type'], $data, $user);
    }

    private function givenATrackerAndItsFactories(): array
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $factory = $this->createMock(\Tracker_FormElementFactory::class);
        $tracker->setFormElementFactory($factory);
        $shared_factory = $this->createMock(\Tracker_SharedFormElementFactory::class);
        $tracker->setSharedFormElementFactory($shared_factory);
        $user = UserTestBuilder::buildWithDefaults();
        return [$tracker, $factory, $shared_factory, $user];
    }

    public function testItHasADefaultWorkflow(): void
    {
        $workflow = $this->createMock(WorkflowWithoutTransition::class);
        $workflow->method('getId')->willReturn($this->tracker_id);
        $this->workflow_factory->method('getNonNullWorkflow')->willReturn($workflow);
        self::assertEquals($workflow, $this->tracker->getWorkflow());
    }

    public function testItAlwaysHaveTheSameDefaultWorkflow(): void
    {
        $workflow = $this->createMock(WorkflowWithoutTransition::class);
        $this->workflow_factory->method('getNonNullWorkflow')->willReturn($workflow);
        self::assertEquals($this->tracker->getWorkflow(), $this->tracker->getWorkflow());
    }

    public function testItHasAWorkflowFromTheFactoryWhenThereAreTransitions(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getId')->willReturn($this->tracker_id);
        $this->workflow_factory->method('getNonNullWorkflow')->with($this->tracker)->willReturn($workflow);
        self::assertEquals($workflow, $this->tracker->getWorkflow());
    }

    public function testItReturnsNullWhenItHasNoParentFromAccessor(): void
    {
        $this->tracker->setParent(null);
        self::assertEquals(null, $this->tracker->getParent());
    }

    public function testItReturnsParentWhenParentWasSetByAccessor(): void
    {
        $parent = $this->createMock(Tracker::class);
        $this->tracker->setParent($parent);
        self::assertEquals($parent, $this->tracker->getParent());
    }

    public function testItReturnsNullWhenItHasNoParentFromDb(): void
    {
        $this->tracker->method('getParentId')->willReturn(null);
        self::assertEquals(null, $this->tracker->getParent());
    }

    public function testItReturnsNullWhenParentNotFoundInDb(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(15)->willReturn(null);
        $this->tracker->method('getParentId')->willReturn(15);
        self::assertEquals(null, $this->tracker->getParent());
    }

    public function testItReturnsParentWhenFetchedFromDb(): void
    {
        $parent = $this->createMock(Tracker::class);
        $this->tracker_factory->method('getTrackerById')->with(15)->willReturn($parent);
        $this->tracker->method('getParentId')->willReturn(15);
        self::assertEquals($parent, $this->tracker->getParent());
    }

    public function testItDoesntFetchParentTwiceWhenThereIsParent(): void
    {
        $parent = $this->createMock(Tracker::class);
        $this->tracker_factory->expects($this->once())->method('getTrackerById')->with(15)->willReturn($parent);
        $this->tracker->method('getParentId')->willReturn(15);

        $this->tracker->getParent();
        $this->tracker->getParent();
    }

    public function testItDoesntFetchParentTwiceWhenOrphan(): void
    {
        $this->tracker_factory->expects($this->once())->method('getTrackerById')->with(15)->willReturn(null);
        $this->tracker->method('getParentId')->willReturn(15);

        $this->tracker->getParent();
        $this->tracker->getParent();
    }
}
