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

namespace Tuleap\Tracker;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker_CannedResponseManager;
use Tracker_DateReminderManager;
use Tracker_Permission_PermissionController;
use TrackerManager;
use Tuleap\Color\ColorName;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\ForgePermissionsRetrieverStub;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Semantic\TrackerSemanticManager;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;
use Workflow;
use WorkflowManager;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerPermissionsTest extends TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private const PUBLIC_PROJECT_ID = 101;

    private PFUser $all_trackers_admin_user;
    private Project $project_private;
    private Tracker&MockObject $tracker;
    private Tracker&MockObject $tracker1;
    private Tracker&MockObject $tracker2;
    private TrackerManager&MockObject $tracker_manager;
    private TrackerSemanticManager&MockObject $tracker_semantic_manager;
    private Tracker_DateReminderManager&MockObject $tracker_date_reminder_manager;
    private Tracker_CannedResponseManager&MockObject $tracker_canned_response_manager;
    private WorkflowManager&MockObject $workflow_manager;
    private PFUser $site_admin_user;
    private PFUser $project_admin_user;
    private PFUser $tracker1_admin_user;
    private PFUser $tracker2_admin_user;
    private PFUser $project_member_user;
    private PFUser $registered_user;
    private Tracker_Permission_PermissionController&MockObject $permission_controller;
    private Tracker_Permission_PermissionController&MockObject $permission_controller1;
    private Tracker_Permission_PermissionController&MockObject $permission_controller2;

    #[\Override]
    protected function setUp(): void
    {
        $public_project        = ProjectTestBuilder::aProject()->withAccess(Project::ACCESS_PUBLIC)->withId(self::PUBLIC_PROJECT_ID)->build();
        $this->project_private = ProjectTestBuilder::aProject()->withAccess(Project::ACCESS_PRIVATE)->withId(102)->build();

        $tracker_methods       = [
            'getGlobalAdminPermissionsChecker', 'getTrackerSemanticManager',
            'getCannedResponseManager', 'getWorkflowManager', 'getGroupId', 'getId', 'getColor', 'getPermissionsByUgroupId',
            'getPermissionController', 'getTrackerArtifactSubmissionPermission', 'displaySubmit',
            'displayAdminFormElements', 'displayAdminOptions',
        ];
        $this->tracker         = $this->createPartialMock(Tracker::class, $tracker_methods);
        $this->tracker1        = $this->createPartialMock(Tracker::class, $tracker_methods);
        $this->tracker2        = $this->createPartialMock(Tracker::class, $tracker_methods);
        $this->tracker_manager = $this->createMock(TrackerManager::class);

        $permissions_checker = new GlobalAdminPermissionsChecker(ForgePermissionsRetrieverStub::withoutPermission());
        $this->tracker->method('getGlobalAdminPermissionsChecker')->willReturn($permissions_checker);
        $this->tracker1->method('getGlobalAdminPermissionsChecker')->willReturn($permissions_checker);
        $this->tracker2->method('getGlobalAdminPermissionsChecker')->willReturn($permissions_checker);

        $this->tracker->setProject($public_project);
        $this->tracker1->setProject($public_project);
        $this->tracker2->setProject($public_project);

        $this->tracker_semantic_manager = $this->createMock(TrackerSemanticManager::class);
        $this->tracker->method('getTrackerSemanticManager')->willReturn($this->tracker_semantic_manager);
        $this->tracker1->method('getTrackerSemanticManager')->willReturn($this->tracker_semantic_manager);
        $this->tracker2->method('getTrackerSemanticManager')->willReturn($this->tracker_semantic_manager);
        $this->tracker_date_reminder_manager   = $this->createMock(Tracker_DateReminderManager::class);
        $this->tracker_canned_response_manager = $this->createMock(Tracker_CannedResponseManager::class);
        $this->tracker->method('getCannedResponseManager')->willReturn($this->tracker_canned_response_manager);
        $this->tracker1->method('getCannedResponseManager')->willReturn($this->tracker_canned_response_manager);
        $this->tracker2->method('getCannedResponseManager')->willReturn($this->tracker_canned_response_manager);
        $this->workflow_manager = $this->createMock(WorkflowManager::class);
        $this->tracker->method('getWorkflowManager')->willReturn($this->workflow_manager);
        $this->tracker1->method('getWorkflowManager')->willReturn($this->workflow_manager);
        $this->tracker2->method('getWorkflowManager')->willReturn($this->workflow_manager);
        $this->tracker->method('getGroupId')->willReturn(self::PUBLIC_PROJECT_ID);
        $this->tracker->method('getId')->willReturn(110);
        $this->tracker->method('getColor')->willReturn(ColorName::default());
        $this->tracker1->method('getGroupId')->willReturn(self::PUBLIC_PROJECT_ID);
        $this->tracker1->method('getId')->willReturn(111);
        $this->tracker2->method('getGroupId')->willReturn(self::PUBLIC_PROJECT_ID);
        $this->tracker2->method('getId')->willReturn(112);

        $this->tracker->method('getPermissionsByUgroupId')->willReturn([
            1   => ['PERM_1'],
            3   => ['PERM_2'],
            5   => ['PERM_3'],
            115 => ['PERM_3'],
        ]);
        $this->tracker1->method('getPermissionsByUgroupId')->willReturn([
            1001 => [101 => 'PLUGIN_TRACKER_ADMIN'],
        ]);
        $this->tracker2->method('getPermissionsByUgroupId')->willReturn([
            1002 => [102 => 'PLUGIN_TRACKER_ADMIN'],
        ]);

        $this->site_admin_user = UserTestBuilder::anActiveUser()
            ->withId(1)
            ->withoutMemberOfProjects()
            ->withSiteAdministrator()
            ->build();

        $this->project_admin_user = UserTestBuilder::anActiveUser()
            ->withId(123)
            ->withMemberOf($public_project)
            ->withAdministratorOf($public_project)
            ->build();

        $this->all_trackers_admin_user = UserTestBuilder::anActiveUser()
            ->withId(222)
            ->withMemberOf($public_project)
            ->withUserGroupMembership($public_project, 1001, true)
            ->withUserGroupMembership($public_project, 1002, true)
            ->build();

        $this->tracker1_admin_user = UserTestBuilder::anActiveUser()
            ->withId(333)
            ->withMemberOf($public_project)
            ->withUserGroupMembership($public_project, 1001, true)
            ->withUserGroupMembership($public_project, 1002, false)
            ->build();

        $this->tracker2_admin_user = UserTestBuilder::anActiveUser()
            ->withId(444)
            ->withMemberOf($public_project)
            ->withUserGroupMembership($public_project, 1001, false)
            ->withUserGroupMembership($public_project, 1002, true)
            ->build();

        $this->project_member_user = UserTestBuilder::anActiveUser()
            ->withId(555)
            ->withMemberOf($public_project)
            ->withUserGroupMembership($public_project, 1001, false)
            ->withUserGroupMembership($public_project, 1002, false)
            ->build();

        $this->registered_user = UserTestBuilder::anActiveUser()
            ->withId(777)
            ->withoutMemberOfProjects()
            ->withUserGroupMembership($public_project, 1001, false)
            ->withUserGroupMembership($public_project, 1002, false)
            ->build();

        $this->permission_controller = $this->createMock(Tracker_Permission_PermissionController::class);
        $this->tracker->method('getPermissionController')->willReturn($this->permission_controller);

        $this->permission_controller1 = $this->createMock(Tracker_Permission_PermissionController::class);
        $this->tracker1->method('getPermissionController')->willReturn($this->permission_controller1);

        $this->permission_controller2 = $this->createMock(Tracker_Permission_PermissionController::class);
        $this->tracker2->method('getPermissionController')->willReturn($this->permission_controller2);

        $GLOBALS['UGROUPS']        = [
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
    }

    // New artifact permissions
    public function testItDelegatesPermissionToVerifier(): void
    {
        $request_new_artifact = HTTPRequestBuilder::get()->withParam('func', 'new-artifact')->build();

        $this->tracker->method('getTrackerArtifactSubmissionPermission')->willReturn(VerifySubmissionPermissionStub::withSubmitPermission());
        $this->tracker->expects($this->once())->method('displaySubmit');
        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }

    public function testItDoesNotDIsplaySubmitWhenUserHasNoPermissions(): void
    {
        $request_new_artifact = HTTPRequestBuilder::get()->withParam('func', 'new-artifact')->build();

        $this->tracker->method('getTrackerArtifactSubmissionPermission')->willReturn(VerifySubmissionPermissionStub::withoutSubmitPermission());
        $this->tracker->expects($this->never())->method('displaySubmit');

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        $this->tracker->process($this->tracker_manager, $request_new_artifact, $this->site_admin_user);
    }

    // Tracker admin permissions
    public function testPermsAdminTrackerSiteAdmin(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        // site admin can access tracker admin part
        $this->tracker->expects($this->once())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->site_admin_user);
    }

    public function testPermsAdminTrackerProjectAdmin(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        // project admin can access tracker admin part
        $this->tracker->expects($this->once())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_admin_user);
    }

    public function testPermsAdminTrackerTrackerAdmin(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->once())->method('displayAdminFormElements');
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
        $this->tracker2->expects($this->once())->method('displayAdminFormElements');
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->all_trackers_admin_user);
    }

    public function testPermsAdminTrackerTracker1Admin(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->once())->method('displayAdminFormElements');
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
        $this->tracker2->expects($this->never())->method('displayAdminFormElements');
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminTrackerTracker2Admin(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->never())->method('displayAdminFormElements');
        $this->tracker1->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
        $this->tracker2->expects($this->once())->method('displayAdminFormElements');
        $this->tracker2->process($this->tracker_manager, $request_admin_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminTrackerProjectMember(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->tracker->expects($this->never())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->project_member_user);
    }

    public function testPermsAdminTrackerRegisteredUser(): void
    {
        $request_admin_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->tracker->expects($this->never())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_tracker, $this->registered_user);
    }

    public function testItCachesTrackerAdminPermission(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(101);
        $user->expects($this->once())->method('isSuperUser');
        $user->expects($this->once())->method('isAdmin')->willReturn(false);

        $this->tracker->userIsAdmin($user);
        $this->tracker->userIsAdmin($user);
    }

    // Tracker admin edit option permissions
    public function testPermsAdminEditOptionsTrackerSiteAdmin(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        // site admin can access tracker admin part
        $this->tracker->expects($this->once())->method('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->site_admin_user);
    }

    public function testPermsAdminEditOptionsTrackerProjectAdmin(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        // project admin can access tracker admin part
        $this->tracker->expects($this->once())->method('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_admin_user);
    }

    public function testPermsAdminEditOptionsTrackerTrackerAdmin(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->once())->method('displayAdminOptions');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->all_trackers_admin_user
        );
        $this->tracker2->expects($this->once())->method('displayAdminOptions');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminEditOptionsTrackerTracker1Admin(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->once())->method('displayAdminOptions');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker1_admin_user
        );
        $this->tracker2->expects($this->never())->method('displayAdminOptions');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker1_admin_user
        );
    }

    public function testPermsAdminEditOptionsTrackerTracker2Admin(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->never())->method('displayAdminOptions');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker2_admin_user
        );
        $this->tracker2->expects($this->once())->method('displayAdminOptions');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_editoptions_tracker,
            $this->tracker2_admin_user
        );
    }

    public function testPermsAdminEditOptionsTrackerProjectMember(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->tracker->expects($this->never())->method('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->project_member_user);
    }

    public function testPermsAdminEditOptionsTrackerRegisteredUser(): void
    {
        $request_admin_editoptions_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-editoptions')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->tracker->expects($this->never())->method('displayAdminOptions');
        $this->tracker->process($this->tracker_manager, $request_admin_editoptions_tracker, $this->registered_user);
    }

    // Tracker "admin perms" permissions
    public function testPermsAdminPermsTrackerSiteAdmin(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        // site admin can access tracker admin part
        $this->permission_controller->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->site_admin_user);
    }

    public function testPermsAdminPermsTrackerProjectAdmin(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        // project admin can access tracker admin part
        $this->permission_controller->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_admin_user);
    }

    public function testPermsAdminPermsTrackerTrackerAdmin(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        // tracker admin can access tracker admin part
        $this->permission_controller1->expects($this->once())->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
        $this->permission_controller2->expects($this->once())->method('process');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->all_trackers_admin_user);
    }

    public function testPermsAdminPermsTrackerTracker1Admin(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');
        // tracker admin can access tracker admin part
        $this->permission_controller1->expects($this->once())->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
        $this->permission_controller2->expects($this->never())->method('process');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminPermsTrackerTracker2Admin(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->permission_controller1->expects($this->never())->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
        $this->permission_controller2->expects($this->once())->method('process');
        $this->tracker2->process($this->tracker_manager, $request_admin_perms_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminPermsTrackerProjectMember(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->permission_controller->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->project_member_user);
    }

    public function testPermsAdminPermsTrackerRegisteredUser(): void
    {
        $request_admin_perms_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->permission_controller->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker, $this->registered_user);
    }

    // Tracker "admin perms tracker" permissions
    public function testPermsAdminPermsTrackerTrackerSiteAdmin(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        // site admin can access tracker admin part
        $this->permission_controller->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->site_admin_user);
    }

    public function testPermsAdminPermsTrackerTrackerProjectAdmin(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        // project admin can access tracker admin part
        $this->permission_controller->expects($this->once())->method('process');
        $this->tracker->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->project_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerTrackerAdmin(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        // tracker admin can access tracker admin part
        $this->permission_controller1->expects($this->once())->method('process');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->all_trackers_admin_user
        );
        $this->permission_controller2->expects($this->once())->method('process');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerTracker1Admin(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->permission_controller1->expects($this->once())->method('process');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker1_admin_user
        );
        $this->permission_controller2->expects($this->never())->method('process');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker1_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerTracker2Admin(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->permission_controller1->expects($this->never())->method('process');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker2_admin_user
        );
        $this->permission_controller2->expects($this->once())->method('process');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->tracker2_admin_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerProjectMember(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->permission_controller->expects($this->never())->method('process');
        $this->tracker->process(
            $this->tracker_manager,
            $request_admin_perms_tracker_tracker,
            $this->project_member_user
        );
    }

    public function testPermsAdminPermsTrackerTrackerRegisteredUser(): void
    {
        $request_admin_perms_tracker_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-perms-tracker')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->permission_controller->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_perms_tracker_tracker, $this->registered_user);
    }

    // Tracker "admin form elements" permissions
    public function testPermsAdminFormElementTrackerSiteAdmin(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        // site admin can access tracker admin part
        $this->tracker->expects($this->once())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->site_admin_user);
    }

    public function testPermsAdminFormElementTrackerProjectAdmin(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        // project admin can access tracker admin part
        $this->tracker->expects($this->once())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_admin_user);
    }

    public function testPermsAdminFormElementTrackerTrackerAdmin(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->once())->method('displayAdminFormElements');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->all_trackers_admin_user
        );
        $this->tracker2->expects($this->once())->method('displayAdminFormElements');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->all_trackers_admin_user
        );
    }

    public function testPermsAdminFormElementTrackerTracker1Admin(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->once())->method('displayAdminFormElements');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker1_admin_user
        );
        $this->tracker2->expects($this->never())->method('displayAdminFormElements');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker1_admin_user
        );
    }

    public function testPermsAdminFormElementTrackerTracker2Admin(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');
        // tracker admin can access tracker admin part
        $this->tracker1->expects($this->never())->method('displayAdminFormElements');
        $this->tracker1->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker2_admin_user
        );
        $this->tracker2->expects($this->once())->method('displayAdminFormElements');
        $this->tracker2->process(
            $this->tracker_manager,
            $request_admin_formelement_tracker,
            $this->tracker2_admin_user
        );
    }

    public function testPermsAdminFormElementTrackerProjectMember(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->tracker->expects($this->never())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->project_member_user);
    }

    public function testPermsAdminFormElementTrackerRegisteredUser(): void
    {
        $request_admin_formelement_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-formElements')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->tracker->expects($this->never())->method('displayAdminFormElements');
        $this->tracker->process($this->tracker_manager, $request_admin_formelement_tracker, $this->registered_user);
    }

    // Tracker "admin semantic" permissions
    public function testPermsAdminSemanticTrackerSiteAdmin(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        // site admin can access tracker admin part
        $this->tracker_semantic_manager->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->site_admin_user);
    }

    public function testPermsAdminSemanticTrackerProjectAdmin(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        // project admin can access tracker admin part
        $this->tracker_semantic_manager->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_admin_user);
    }

    public function testPermsAdminSemanticTrackerTrackerAdmin(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        // tracker admin can access tracker admin part
        $this->tracker_semantic_manager->expects($this->exactly(2))->method('process');
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

    public function testPermsAdminSemanticTrackerTracker1Admin(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker_semantic_manager->expects($this->once())->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminSemanticTrackerTracker2Admin(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
        $this->tracker_semantic_manager->expects($this->once())->method('process');
        $this->tracker2->process($this->tracker_manager, $request_admin_semantic_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminSemanticTrackerProjectMember(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->tracker_semantic_manager->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->project_member_user);
    }

    public function testPermsAdminSemanticTrackerRegisteredUser(): void
    {
        $request_admin_semantic_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-semantic')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->tracker_semantic_manager->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_semantic_tracker, $this->registered_user);
    }

    // Tracker "admin canned" permissions
    public function testPermsAdminCannedTrackerSiteAdmin(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        // site admin can access tracker admin part
        $this->tracker_canned_response_manager->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->site_admin_user);
    }

    public function testPermsAdminCannedTrackerProjectAdmin(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        // project admin can access tracker admin part
        $this->tracker_canned_response_manager->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_admin_user);
    }

    public function testPermsAdminCannedTrackerTrackerAdmin(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        // tracker admin can access tracker admin part
        $this->tracker_canned_response_manager->expects($this->exactly(2))->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->all_trackers_admin_user);
    }

    public function testPermsAdminCannedTrackerTracker1Admin(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker_canned_response_manager->expects($this->once())->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminCannedTrackerTracker2Admin(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
        $this->tracker_canned_response_manager->expects($this->once())->method('process');
        $this->tracker2->process($this->tracker_manager, $request_admin_canned_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminCannedTrackerProjectMember(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->tracker_canned_response_manager->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->project_member_user);
    }

    public function testPermsAdminCannedTrackerRegisteredUser(): void
    {
        $request_admin_canned_tracker = HTTPRequestBuilder::get()->withParam('func', 'admin-canned')->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->tracker_canned_response_manager->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_canned_tracker, $this->registered_user);
    }

    // Tracker "admin workflow" permissions
    public function testPermsAdminWorkflowTrackerSiteAdmin(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        // site admin can access tracker admin part
        $this->workflow_manager->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->site_admin_user);
    }

    public function testPermsAdminWorkflowTrackerProjectAdmin(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        // project admin can access tracker admin part
        $this->workflow_manager->expects($this->once())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_admin_user);
    }

    public function testPermsAdminWorkflowTrackerTrackerAdmin(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        // tracker admin can access tracker admin part
        $this->workflow_manager->expects($this->exactly(2))->method('process');
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

    public function testPermsAdminWorkflowTrackerTracker1Admin(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->workflow_manager->expects($this->once())->method('process');
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker1_admin_user);
    }

    public function testPermsAdminWorkflowTrackerTracker2Admin(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // tracker admin can access tracker admin part
        $this->tracker1->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
        $this->workflow_manager->expects($this->once())->method('process');
        $this->tracker2->process($this->tracker_manager, $request_admin_workflow_tracker, $this->tracker2_admin_user);
    }

    public function testPermsAdminWorkflowTrackerProjectMember(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // project member can NOT access tracker admin part
        $this->workflow_manager->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->project_member_user);
    }

    public function testPermsAdminWorkflowTrackerRegisteredUser(): void
    {
        $request_admin_workflow_tracker = HTTPRequestBuilder::get()->withParam('func', Workflow::FUNC_ADMIN_TRANSITIONS)->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', self::anything());
        $GLOBALS['Response']->expects($this->once())->method('redirect');

        // registered user can NOT access tracker admin part
        $this->workflow_manager->expects($this->never())->method('process');
        $this->tracker->process($this->tracker_manager, $request_admin_workflow_tracker, $this->registered_user);
    }
}
