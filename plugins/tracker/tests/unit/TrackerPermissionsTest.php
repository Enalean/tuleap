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

namespace Tuleap\Tracker;

use Codendi_Request;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use Tracker;
use Tracker_CannedResponseFactory;
use Tracker_CannedResponseManager;
use Tracker_DateReminderManager;
use Tracker_FormElementFactory;
use Tracker_Hierarchy;
use Tracker_HierarchyFactory;
use Tracker_NotificationsManager;
use Tracker_Permission_PermissionController;
use Tracker_ReportFactory;
use Tracker_SemanticManager;
use TrackerFactory;
use TrackerManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;
use User_ForgeUserGroupPermissionsManager;
use UserManager;
use Workflow;
use WorkflowFactory;
use WorkflowManager;

final class TrackerPermissionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    private $all_trackers_admin_user;

    private Project $project;
    private Project $project_private;
    /**
     * @var Mockery\Mock|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\Mock|Tracker
     */
    private $tracker1;
    /**
     * @var Mockery\Mock|Tracker
     */
    private $tracker2;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerManager
     */
    private $tracker_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tf;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_NotificationsManager
     */
    private $tnm;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_SemanticManager
     */
    private $tsm;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_DateReminderManager
     */
    private $trr;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_CannedResponseManager
     */
    private $tcrm;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WorkflowManager
     */
    private $wm;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $site_admin_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $project_admin_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $tracker1_admin_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $tracker2_admin_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $project_member_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $registered_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WorkflowFactory
     */
    private $workflow_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ReportFactory
     */
    private $report_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_CannedResponseFactory
     */
    private $canned_response_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Permission_PermissionController
     */
    private $permission_controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Permission_PermissionController
     */
    private $permission_controller1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Permission_PermissionController
     */
    private $permission_controller2;
    /**
     * @var Tracker_Hierarchy
     */
    private $hierarchy;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;

    public function setUp(): void
    {
        $this->project         = ProjectTestBuilder::aProject()->withAccess(\Project::ACCESS_PUBLIC)->withId(101)->build();
        $this->project_private = ProjectTestBuilder::aProject()->withAccess(\Project::ACCESS_PRIVATE)->withId(102)->build();

        $this->tracker         = Mockery::mock(Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker1        = Mockery::mock(Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker2        = Mockery::mock(Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker_manager = Mockery::mock(TrackerManager::class);

        $forge_user_group_permissions_manager = Mockery::mock(User_ForgeUserGroupPermissionsManager::class);
        $permissions_checker                  = new GlobalAdminPermissionsChecker($forge_user_group_permissions_manager);

        $this->tracker->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($permissions_checker);
        $this->tracker1->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($permissions_checker);
        $this->tracker2->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($permissions_checker);

        $forge_user_group_permissions_manager->shouldReceive('doesUserHavePermission')->andReturn(false);

        $this->tracker->setProject($this->project);
        $this->tracker1->setProject($this->project);
        $this->tracker2->setProject($this->project);

        $this->tf = Mockery::mock(TrackerFactory::class);
        $this->tracker->shouldReceive('getTrackerFactory')->andReturns($this->tf);
        $this->tracker1->shouldReceive('getTrackerFactory')->andReturns($this->tf);
        $this->tracker2->shouldReceive('getTrackerFactory')->andReturns($this->tf);
        $this->tsm = Mockery::mock(Tracker_SemanticManager::class);
        $this->tracker->shouldReceive('getTrackerSemanticManager')->andReturns($this->tsm);
        $this->tracker1->shouldReceive('getTrackerSemanticManager')->andReturns($this->tsm);
        $this->tracker2->shouldReceive('getTrackerSemanticManager')->andReturns($this->tsm);
        $this->tnm = Mockery::mock(Tracker_NotificationsManager::class);
        $this->tracker->shouldReceive('getNotificationsManager')->andReturns($this->tnm);
        $this->tracker1->shouldReceive('getNotificationsManager')->andReturns($this->tnm);
        $this->tracker2->shouldReceive('getNotificationsManager')->andReturns($this->tnm);
        $this->trr = Mockery::mock(Tracker_DateReminderManager::class);
        $this->tracker->shouldReceive('getDateReminderManager')->andReturns($this->trr);
        $this->tracker1->shouldReceive('getDateReminderManager')->andReturns($this->trr);
        $this->tracker2->shouldReceive('getDateReminderManager')->andReturns($this->trr);
        $this->tcrm = Mockery::mock(Tracker_CannedResponseManager::class);
        $this->tracker->shouldReceive('getCannedResponseManager')->andReturns($this->tcrm);
        $this->tracker1->shouldReceive('getCannedResponseManager')->andReturns($this->tcrm);
        $this->tracker2->shouldReceive('getCannedResponseManager')->andReturns($this->tcrm);
        $this->wm = Mockery::mock(WorkflowManager::class);
        $this->tracker->shouldReceive('getWorkflowManager')->andReturns($this->wm);
        $this->tracker1->shouldReceive('getWorkflowManager')->andReturns($this->wm);
        $this->tracker2->shouldReceive('getWorkflowManager')->andReturns($this->wm);
        $group_id = 101;
        $this->tracker->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker->shouldReceive('getId')->andReturns(110);
        $this->tracker->shouldReceive('getColor')->andReturns(TrackerColor::default());
        $this->tracker1->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker1->shouldReceive('getId')->andReturns(111);
        $this->tracker2->shouldReceive('getGroupId')->andReturns($group_id);
        $this->tracker2->shouldReceive('getId')->andReturns(112);

        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturns(
            [
                1   => ['PERM_1'],
                3   => ['PERM_2'],
                5   => ['PERM_3'],
                115 => ['PERM_3'],
            ]
        );
        $this->tracker1->shouldReceive('getPermissionsByUgroupId')->andReturns(
            [
                1001 => [101 => 'PLUGIN_TRACKER_ADMIN'],
            ]
        );
        $this->tracker2->shouldReceive('getPermissionsByUgroupId')->andReturns(
            [
                1002 => [102 => 'PLUGIN_TRACKER_ADMIN'],
            ]
        );

        $this->site_admin_user = Mockery::mock(PFUser::class);
        $this->site_admin_user->shouldReceive('getId')->andReturns(1);
        $this->site_admin_user->shouldReceive('isMember')->andReturns(false);
        $this->site_admin_user->shouldReceive('isAdmin')->andReturns(false);
        $this->site_admin_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->site_admin_user->shouldReceive('isSuperUser')->andReturns(true);
        $this->site_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(false);
        $this->site_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(false);
        $this->site_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->project_admin_user = Mockery::mock(PFUser::class);
        $this->project_admin_user->shouldReceive('getId')->andReturns(123);
        $this->project_admin_user->shouldReceive('isAdmin')->with($group_id)->andReturns(true);
        $this->project_admin_user->shouldReceive('isMember')->withArgs([102])->andReturns(false);
        $this->project_admin_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->project_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->project_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(
            false
        );
        $this->project_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(
            false
        );
        $this->project_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->all_trackers_admin_user = Mockery::mock(PFUser::class);
        $this->all_trackers_admin_user->shouldReceive('getId')->andReturns(222);
        $this->all_trackers_admin_user->shouldReceive('isAdmin')->with($group_id)->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isMember')->withArgs([102])->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->all_trackers_admin_user->shouldReceive('isMember')->withArgs([$group_id, 0])->andReturns(true);
        $this->all_trackers_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(
            true
        ); //1001 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(
            true
        ); //1002 = ugroup who has ADMIN perm on tracker
        $this->all_trackers_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->tracker1_admin_user = Mockery::mock(PFUser::class);
        $this->tracker1_admin_user->shouldReceive('getId')->andReturns(333);
        $this->tracker1_admin_user->shouldReceive('isAdmin')->with($group_id)->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isMember')->withArgs([102])->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker1_admin_user->shouldReceive('isMember')->withArgs([$group_id, 0])->andReturns(true);
        $this->tracker1_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(
            true
        );
        $this->tracker1_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(
            false
        );
        $this->tracker1_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->tracker2_admin_user = Mockery::mock(PFUser::class);
        $this->tracker2_admin_user->shouldReceive('getId')->andReturns(444);
        $this->tracker2_admin_user->shouldReceive('isAdmin')->with($group_id)->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isMember')->withArgs([102])->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker2_admin_user->shouldReceive('isMember')->withArgs([$group_id, 0])->andReturns(true);
        $this->tracker2_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(
            false
        );
        $this->tracker2_admin_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(
            true
        );
        $this->tracker2_admin_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->project_member_user = Mockery::mock(PFUser::class);
        $this->project_member_user->shouldReceive('getId')->andReturns(555);
        $this->project_member_user->shouldReceive('isAdmin')->with($group_id)->andReturns(false);
        $this->project_member_user->shouldReceive('isMember')->withArgs([102])->andReturns(false);
        $this->project_member_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->project_member_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->project_member_user->shouldReceive('isMember')->withArgs([$group_id, 0])->andReturns(true);
        $this->project_member_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(
            false
        );
        $this->project_member_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(
            false
        );
        $this->project_member_user->shouldReceive('isTrackerAdmin')->andReturns(false);
        $this->project_member_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->registered_user = Mockery::mock(PFUser::class);
        $this->registered_user->shouldReceive('getId')->andReturns(777);
        $this->registered_user->shouldReceive('isAdmin')->andReturns(false);
        $this->registered_user->shouldReceive('isMember')->andReturns(false);
        $this->registered_user->shouldReceive('isAnonymous')->andReturns(false);
        $this->registered_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->registered_user->shouldReceive('isMemberOfUGroup')->withArgs([1001, Mockery::any()])->andReturns(false);
        $this->registered_user->shouldReceive('isMemberOfUGroup')->withArgs([1002, Mockery::any()])->andReturns(false);
        $this->registered_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->workflow_factory = Mockery::mock(WorkflowFactory::class);
        $this->tracker->shouldReceive('getWorkflowFactory')->andReturns($this->workflow_factory);

        $this->formelement_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->tracker->shouldReceive('getFormElementFactory')->andReturns($this->formelement_factory);

        $this->report_factory = Mockery::mock(Tracker_ReportFactory::class);
        $this->tracker->shouldReceive('getReportFactory')->andReturns($this->report_factory);

        $this->canned_response_factory = Mockery::mock(Tracker_CannedResponseFactory::class);
        $this->tracker->shouldReceive('getCannedResponseFactory')->andReturns($this->canned_response_factory);

        $this->permission_controller = Mockery::mock(Tracker_Permission_PermissionController::class);
        $this->tracker->shouldReceive('getPermissionController')->andReturns($this->permission_controller);

        $this->permission_controller1 = Mockery::mock(Tracker_Permission_PermissionController::class);
        $this->tracker1->shouldReceive('getPermissionController')->andReturn($this->permission_controller1);

        $this->permission_controller2 = Mockery::mock(Tracker_Permission_PermissionController::class);
        $this->tracker2->shouldReceive('getPermissionController')->andReturn($this->permission_controller2);

        $this->hierarchy   = new Tracker_Hierarchy();
        $hierarchy_factory = Mockery::mock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getHierarchy')->andReturn($this->hierarchy);
        $this->tracker->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);

        $this->workflow_factory = Mockery::mock(WorkflowFactory::class);
        WorkflowFactory::setInstance($this->workflow_factory);

        $this->user_manager = Mockery::mock(UserManager::class);
        UserManager::setInstance($this->user_manager);

        $GLOBALS['Response'] = Mockery::mock(BaseLayout::class);

        $GLOBALS['UGROUPS'] = [
            'UGROUP_NONE'               => 100,
            'UGROUP_ANONYMOUS'          => 1,
            'UGROUP_REGISTERED'         => 2,
            'UGROUP_AUTHENTICATED'      => 5,
            'UGROUP_PROJECT_MEMBERS'    => 3,
            'UGROUP_PROJECT_ADMIN'      => 4,
            'UGROUP_FILE_MANAGER_ADMIN' => 11,
            'UGROUP_WIKI_ADMIN'         => 14,
            'UGROUP_TRACKER_ADMIN'      => 15,
        ];
    }

    public function tearDown(): void
    {
        WorkflowFactory::clearInstance();
        UserManager::clearInstance();
        unset($GLOBALS['Response'], $GLOBALS['_SESSION']);
        parent::tearDown();
    }

    // New artifact permissions
    public function testItDelegatesPermissionToVerifier(): void
    {
        $request_new_artifact = Mockery::mock(Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->withArgs(['func'])->andReturns('new-artifact');

        $this->tracker->shouldReceive('getTrackerArtifactSubmissionPermission')->andReturns(VerifySubmissionPermissionStub::withSubmitPermission());
        $this->tracker->shouldReceive('displaySubmit')->once();
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }

    public function testItDoesNotDIsplaySubmitWhenUserHasNoPermissions(): void
    {
        $request_new_artifact = Mockery::mock(Codendi_Request::class);
        $request_new_artifact->shouldReceive('get')->withArgs(['func'])->andReturns('new-artifact');

        $this->tracker->shouldReceive('getTrackerArtifactSubmissionPermission')->andReturns(VerifySubmissionPermissionStub::withoutSubmitPermission());
        $this->tracker->shouldReceive('displaySubmit')->never();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }

    // Tracker admin permissions
    public function testPermsAdminTrackerSiteAdmin()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');
        $request_admin_tracker->shouldReceive('get')->withArgs(['add-formElement']);
        $request_admin_tracker->shouldReceive('get')->withArgs(['create-formElement']);

        // site admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->site_admin_user);
    }

    public function testPermsAdminTrackerProjectAdmin()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');
        $request_admin_tracker->shouldReceive('get')->withArgs(['add-formElement']);
        $request_admin_tracker->shouldReceive('get')->withArgs(['create-formElement']);

        // project admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_admin_user);
    }

    public function testPermsAdminTrackerTrackerAdmin()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');
        $request_admin_tracker->shouldReceive('get')->withArgs(['add-formElement']);
        $request_admin_tracker->shouldReceive('get')->withArgs(['create-formElement']);

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
    }

    public function testPermsAdminTrackerTracker1Admin()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');
        $request_admin_tracker->shouldReceive('get')->withArgs(['add-formElement'])->once();
        $request_admin_tracker->shouldReceive('get')->withArgs(['create-formElement'])->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminTrackerTracker2Admin()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');
        $request_admin_tracker->shouldReceive('get')->withArgs(['add-formElement'])->once();
        $request_admin_tracker->shouldReceive('get')->withArgs(['create-formElement'])->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminTrackerProjectMember()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_member_user);
    }

    public function testPermsAdminTrackerRegisteredUser()
    {
        $request_admin_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->registered_user);
    }

    public function testItCachesTrackerAdminPermission()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);
        $user->shouldReceive('isSuperUser')->once();
        $user->shouldReceive('isAdmin')->once()->andReturn(false);

        $this->tracker->userIsAdmin($user);
        $this->tracker->userIsAdmin($user);
    }

    // Tracker admin edit option permissions
    public function testPermsAdminEditOptionsTrackerSiteAdmin()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['update']);

        // site admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->site_admin_user);
    }

    public function testPermsAdminEditOptionsTrackerProjectAdmin()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['update']);

        // project admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_admin_user);
    }

    public function testPermsAdminEditOptionsTrackerTrackerAdmin()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['update'])->twice();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminOptions')->once();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->all_trackers_admin_user
        );
        $this->tracker2->shouldReceive('displayAdminOptions')->once();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminEditOptionsTrackerTracker1Admin()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['update'])->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminOptions')->once();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker1_admin_user
        );
        $this->tracker2->shouldReceive('displayAdminOptions')->never();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker1_admin_user
        );
    }

    public function testPermsAdminEditOptionsTrackerTracker2Admin()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['update'])->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminOptions')->never();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker2_admin_user
        );
        $this->tracker2->shouldReceive('displayAdminOptions')->once();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker2_admin_user
        );
    }

    public function testPermsAdminEditOptionsTrackerProjectMember()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_member_user);
    }

    public function testPermsAdminEditOptionsTrackerRegisteredUser()
    {
        $request_admin_editoptions_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_editoptions_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-editoptions');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminOptions')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->registered_user);
    }

    // Tracker "admin perms" permissions
    public function testPermsAdminPermsTrackerSiteAdmin()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        // site admin can access tracker admin part
        $this->permission_controller->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->site_admin_user);
    }

    public function testPermsAdminPermsTrackerProjectAdmin()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        // project admin can access tracker admin part
        $this->permission_controller->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_admin_user);
    }

    public function testPermsAdminPermsTrackerTrackerAdmin()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        // tracker admin can access tracker admin part
        $this->permission_controller1->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
        $this->permission_controller2->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
    }

    public function testPermsAdminPermsTrackerTracker1Admin()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();
        // tracker admin can access tracker admin part
        $this->permission_controller1->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
        $this->permission_controller2->shouldReceive('process')->never();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminPermsTrackerTracker2Admin()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->permission_controller1->shouldReceive('process')->never();
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
        $this->permission_controller2->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminPermsTrackerProjectMember()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->permission_controller->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_member_user);
    }

    public function testPermsAdminPermsTrackerRegisteredUser()
    {
        $request_admin_perms_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-perms');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->permission_controller->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->registered_user);
    }

    // Tracker "admin perms tracker" permissions
    public function testPermsAdminPermsTrackerTrackerSiteAdmin()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        // site admin can access tracker admin part
        $this->permission_controller->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->site_admin_user);
    }

    public function testPermsAdminPermsTrackerTrackerProjectAdmin()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        // project admin can access tracker admin part
        $this->permission_controller->shouldReceive('process')->once();
        $this->tracker->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->project_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerTrackerAdmin()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        // tracker admin can access tracker admin part
        $this->permission_controller1->shouldReceive('process')->once();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->all_trackers_admin_user
        );
        $this->permission_controller2->shouldReceive('process')->once();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerTracker1Admin()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->permission_controller1->shouldReceive('process')->once();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker1_admin_user
        );
        $this->permission_controller2->shouldReceive('process')->never();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker1_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerTracker2Admin()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->permission_controller1->shouldReceive('process')->never();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker2_admin_user
        );
        $this->permission_controller2->shouldReceive('process')->once();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker2_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerProjectMember()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->permission_controller->shouldReceive('process')->never();
        $this->tracker->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->project_member_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerRegisteredUser()
    {
        $request_admin_perms_tracker_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_perms_tracker_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            'admin-perms-tracker'
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->permission_controller->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->registered_user);
    }

    // Tracker "admin form elements" permissions
    public function testPermsAdminFormElementTrackerSiteAdmin()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['add-formElement'])->once();
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['create-formElement']);

        // site admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->site_admin_user);
    }

    public function testPermsAdminFormElementTrackerProjectAdmin()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['add-formElement'])->once();
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['create-formElement'])->once();

        // project admin can access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_admin_user);
    }

    public function testPermsAdminFormElementTrackerTrackerAdmin()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['add-formElement'])->twice();
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['create-formElement'])->twice();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->all_trackers_admin_user
        );
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminFormElementTrackerTracker1Admin()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['add-formElement'])->once();
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['create-formElement'])->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->once();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker1_admin_user
        );
        $this->tracker2->shouldReceive('displayAdminFormElements')->never();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker1_admin_user
        );
    }

    public function testPermsAdminFormElementTrackerTracker2Admin()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['add-formElement'])->once();
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['create-formElement'])->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();
        // tracker admin can access tracker admin part
        $this->tracker1->shouldReceive('displayAdminFormElements')->never();
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker2_admin_user
        );
        $this->tracker2->shouldReceive('displayAdminFormElements')->once();
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker2_admin_user
        );
    }

    public function testPermsAdminFormElementTrackerProjectMember()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_member_user);
    }

    public function testPermsAdminFormElementTrackerRegisteredUser()
    {
        $request_admin_formelement_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_formelement_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-formElements');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->tracker->shouldReceive('displayAdminFormElements')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->registered_user);
    }

    // Tracker "admin semantic" permissions
    public function testPermsAdminSemanticTrackerSiteAdmin()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        // site admin can access tracker admin part
        $this->tsm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->site_admin_user);
    }

    public function testPermsAdminSemanticTrackerProjectAdmin()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        // project admin can access tracker admin part
        $this->tsm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_admin_user);
    }

    public function testPermsAdminSemanticTrackerTrackerAdmin()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        // tracker admin can access tracker admin part
        $this->tsm->shouldReceive('process')->times(2);
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_semantic_tracker,
            $this->all_trackers_admin_user
        );
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_semantic_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminSemanticTrackerTracker1Admin()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tsm->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminSemanticTrackerTracker2Admin()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
        $this->tsm->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminSemanticTrackerProjectMember()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->tsm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_member_user);
    }

    public function testPermsAdminSemanticTrackerRegisteredUser()
    {
        $request_admin_semantic_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_semantic_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-semantic');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->tsm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->registered_user);
    }

    // Tracker "admin canned" permissions
    public function testPermsAdminCannedTrackerSiteAdmin()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        // site admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->site_admin_user);
    }

    public function testPermsAdminCannedTrackerProjectAdmin()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        // project admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_admin_user);
    }

    public function testPermsAdminCannedTrackerTrackerAdmin()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        // tracker admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->times(2);
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
    }

    public function testPermsAdminCannedTrackerTracker1Admin()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminCannedTrackerTracker2Admin()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
        $this->tcrm->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminCannedTrackerProjectMember()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->tcrm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_member_user);
    }

    public function testPermsAdminCannedTrackerRegisteredUser()
    {
        $request_admin_canned_tracker = Mockery::mock(Codendi_Request::class);
        $request_admin_canned_tracker->shouldReceive('get')->withArgs(['func'])->andReturns('admin-canned');

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->tcrm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->registered_user);
    }

    // Tracker "admin workflow" permissions
    public function testPermsAdminWorkflowTrackerSiteAdmin()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        // site admin can access tracker admin part
        $this->wm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->site_admin_user);
    }

    public function testPermsAdminWorkflowTrackerProjectAdmin()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        // project admin can access tracker admin part
        $this->wm->shouldReceive('process')->once();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_admin_user);
    }

    public function testPermsAdminWorkflowTrackerTrackerAdmin()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        // tracker admin can access tracker admin part
        $this->wm->shouldReceive('process')->times(2);
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_workflow_tracker,
            $this->all_trackers_admin_user
        );
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_workflow_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminWorkflowTrackerTracker1Admin()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->wm->shouldReceive('process')->once();
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminWorkflowTrackerTracker2Admin()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
        $this->wm->shouldReceive('process')->once();
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminWorkflowTrackerProjectMember()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // project member can NOT access tracker admin part
        $this->wm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_member_user);
    }

    public function testPermsAdminWorkflowTrackerRegisteredUser()
    {
        $request_admin_workflow_tracker = Mockery::mock(HTTPRequest::class);
        $request_admin_workflow_tracker->shouldReceive('get')->withArgs(['func'])->andReturns(
            Workflow::FUNC_ADMIN_TRANSITIONS
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs(['error', Mockery::any()])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        // registered user can NOT access tracker admin part
        $this->wm->shouldReceive('process')->never();
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->registered_user);
    }
}
