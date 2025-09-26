<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Permission_PermissionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private PFUser&MockObject $restricted;
    private UserManager&MockObject $user_manager;
    private PFUser&MockObject $user;
    private PFUser&MockObject $assignee;
    private PFUser&MockObject $u_ass;
    private PFUser&MockObject $submitter;
    private PFUser&MockObject $u_sub;
    private PFUser&MockObject $other;
    private Tracker&MockObject $tracker;
    private ProjectAccessChecker&MockObject $project_access_checker;
    private Tracker_Permission_PermissionChecker $permission_checker;
    private Project&MockObject $project;
    private PFUser&MockObject $anonymous;
    private PFUser&MockObject $registered;
    private PFUser&MockObject $project_member;
    private PFUser&MockObject $project_admin;
    private PFUser&MockObject $super_admin;
    private PFUser&MockObject $tracker_submitter;
    private PFUser&MockObject $tracker_assignee;
    private PFUser&MockObject $tracker_submitterassignee;
    private PFUser&MockObject $tracker_admin;
    private PFUser&MockObject $all_trackers_forge_admin_user;
    private Project&MockObject $project_private;
    private GlobalAdminPermissionsChecker&MockObject $global_admin_permissions_checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = $this->createMock(\Project::class);
        $this->project->method('getID')->willReturn(120);
        $this->project->method('isPublic')->willReturn(true);
        $this->project->method('isActive')->willReturn(true);

        $this->project_private = $this->createMock(\Project::class);
        $this->project_private->method('getID')->willReturn(102);
        $this->project_private->method('isPublic')->willReturn(false);
        $this->project_private->method('isActive')->willReturn(true);

        $this->user_manager           = $this->createMock(\UserManager::class);
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);

        $this->global_admin_permissions_checker = $this->createMock(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class);
        $this->global_admin_permissions_checker->method('doesUserHaveTrackerGlobalAdminRightsOnProject')->willReturn(false);

        $this->permission_checker = new Tracker_Permission_PermissionChecker(
            $this->user_manager,
            $this->project_access_checker,
            $this->global_admin_permissions_checker,
        );

        // $assignee and $u_ass are in the same ugroup (UgroupAss - ugroup_id=101)
        // $submitter and $u_sub are in the same ugroup (UgroupSub - ugroup_id=102)
        // $other and $u are neither in UgroupAss nor in UgroupSub

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(120);
        $this->user->method('isMemberOfUGroup')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isMember')->with(12)->willReturn(true);

        $this->assignee = $this->createMock(\PFUser::class);
        $this->assignee->method('getId')->willReturn(121);
        $this->assignee->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => true,
                102 => false,
            }
        );
        $this->assignee->method('isSuperUser')->willReturn(false);
        $this->assignee->method('isMember')->with(12)->willReturn(true);

        $this->u_ass = $this->createMock(\PFUser::class);
        $this->u_ass->method('getId')->willReturn(122);
        $this->u_ass->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => true,
                102 => false,
            }
        );
        $this->u_ass->method('isSuperUser')->willReturn(false);
        $this->u_ass->method('isMember')->with(12)->willReturn(true);

        $this->submitter = $this->createMock(\PFUser::class);
        $this->submitter->method('getId')->willReturn(123);
        $this->submitter->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => false,
                102 => true,
            }
        );
        $this->submitter->method('isSuperUser')->willReturn(false);
        $this->submitter->method('isMember')->with(12)->willReturn(true);

        $this->u_sub = $this->createMock(\PFUser::class);
        $this->u_sub->method('getId')->willReturn(124);
        $this->u_sub->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => false,
                102 => true,
            }
        );
        $this->u_sub->method('isSuperUser')->willReturn(false);
        $this->u_sub->method('isMember')->with(12)->willReturn(true);

        $this->other = $this->createMock(\PFUser::class);
        $this->other->method('getId')->willReturn(125);
        $this->other->method('isMemberOfUGroup')->willReturn(false);
        $this->other->method('isSuperUser')->willReturn(false);
        $this->other->method('isMember')->with(12)->willReturn(true);

        $this->restricted = $this->createMock(\PFUser::class);
        $this->restricted->method('getId')->willReturn(126);
        $this->restricted->method('isMemberOfUGroup')->willReturn(true);
        $this->restricted->method('isSuperUser')->willReturn(false);
        $this->restricted->method('isRestricted')->willReturn(true);

        $this->user_manager->method('getUserById')->willReturnCallback(
            fn (int $id) => match ($id) {
                120 => $this->user,
                121 => $this->assignee,
                122 => $this->u_ass,
                123 => $this->submitter,
                124 => $this->u_sub,
                125 => $this->other,
                126 => $this->restricted,
            }
        );

        $this->tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->tracker->method('getId')->willReturn(666);
        $this->tracker->method('getGroupId')->willReturn(222);
        $this->tracker->method('getProject')->willReturn($this->project);

        $this->anonymous = $this->createMock(\PFUser::class);
        $this->anonymous->method('isSuperUser')->willReturn(false);
        $this->anonymous->method('getId')->willReturn(0);
        $this->anonymous->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1 => true,
                2, 3, 4, 138, 196, 1001, 1002 => false,
            }
        );

        $this->registered = $this->createMock(\PFUser::class);
        $this->registered->method('isSuperUser')->willReturn(false);
        $this->registered->method('getId')->willReturn(101);
        $this->registered->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1, 2 => true,
                3, 4, 138, 196, 1001, 1002 => false,
            }
        );

        $this->project_member = $this->createMock(\PFUser::class);
        $this->project_member->method('isSuperUser')->willReturn(false);
        $this->project_member->method('getId')->willReturn(102);
        $this->project_member->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1, 2, 3 => true,
                4, 138, 196, 1001, 1002 => false,
            }
        );
        $this->project_member->method('isMember')->with(102)->willReturn(false);

        $this->project_admin = $this->createMock(\PFUser::class);
        $this->project_admin->method('isSuperUser')->willReturn(false);
        $this->project_admin->method('getId')->willReturn(103);
        $this->project_admin->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1, 2, 3, 4 => true,
                138, 196, 1001, 1002 => false,
            }
        );
        $this->project_admin->method('isMember')->with(102)->willReturn(false);

        $this->super_admin = $this->createMock(\PFUser::class);
        $this->super_admin->method('isSuperUser')->willReturn(true);
        $this->super_admin->method('getId')->willReturn(104);
        $this->super_admin->method('isAdmin')->willReturn(false);
        $this->super_admin->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
            //                1001, 1002 => false,
                default => true,
            }
        );

        $this->tracker_submitter = $this->createMock(\PFUser::class);
        $this->tracker_submitter->method('isSuperUser')->willReturn(false);
        $this->tracker_submitter->method('getId')->willReturn(105);
        $this->tracker_submitter->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1, 138 => true,
                2, 3, 4, 196, 1001, 1002 => false,
            }
        );
        $this->tracker_submitter->method('isMember')->with(102)->willReturn(false);

        $this->tracker_assignee = $this->createMock(\PFUser::class);
        $this->tracker_assignee->method('isSuperUser')->willReturn(false);
        $this->tracker_assignee->method('getId')->willReturn(106);
        $this->tracker_assignee->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1, 196 => true,
                2, 3, 4, 138, 1001, 1002 => false,
            }
        );
        $this->tracker_assignee->method('isMember')->with(102)->willReturn(false);

        $this->tracker_submitterassignee = $this->createMock(\PFUser::class);
        $this->tracker_submitterassignee->method('isSuperUser')->willReturn(false);
        $this->tracker_submitterassignee->method('getId')->willReturn(107);
        $this->tracker_submitterassignee->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1, 138, 196 => true,
                2, 3, 4, 1001, 1002 => false,
            }
        );
        $this->tracker_submitterassignee->method('isMember')->with(102)->willReturn(false);

        $this->tracker_admin = $this->createMock(\PFUser::class);
        $this->tracker_admin->method('isSuperUser')->willReturn(false);
        $this->tracker_admin->method('getId')->willReturn(107);
        $this->tracker_admin->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                1001 => true,
                1, 2, 3, 4, 138, 196, 1002 => false,
            }
        );
        $this->tracker_admin->method('isMember')->with(102)->willReturn(false);

        $this->all_trackers_forge_admin_user = $this->createMock(\PFUser::class);
        $this->all_trackers_forge_admin_user->method('getId')->willReturn(888);
        $this->all_trackers_forge_admin_user->method('isMember')->willReturn(false);
        $this->all_trackers_forge_admin_user->method('isSuperUser')->willReturn(false);
        $this->all_trackers_forge_admin_user->method('isMemberOfUGroup')->willReturn(false);
    }

    public function testTrackerAccessForUserNotAllowedToAccessToProject(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject')->willThrowException($this->createMock(Project_AccessException::class));

        $this->restricted->method('isMember')->willReturn(true);

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->restricted, $this->tracker));
    }

    public function testAccessPermsAnonymousFullAccess(): void
    {
        $t_access_anonymous = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_anonymous->method('getId')->willReturn(1);
        $t_access_anonymous->method('getGroupId')->willReturn(101);
        $t_access_anonymous->method('getProject')->willReturn($this->project);
        $perms = [
            1 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_anonymous->method('getPermissionsByUgroupId')->willReturn($perms);
        $t_access_anonymous->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_anonymous->method('getUserManager')->willReturn($this->user_manager);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertTrue($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->registered, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_member, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_anonymous));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_anonymous));
    }

    public function testAccessPermsRegisteredFullAccess(): void
    {
        $t_access_registered = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_registered->method('getId')->willReturn(2);
        $t_access_registered->method('getGroupId')->willReturn(101);
        $t_access_registered->method('getProject')->willReturn($this->project);
        $t_access_registered->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_registered->method('getUserManager')->willReturn($this->user_manager);
        $perms = [
            2 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_registered->method('getPermissionsByUgroupId')->willReturn($perms);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_registered));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->registered, $t_access_registered));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_member, $t_access_registered));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_registered));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_registered));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_registered));
    }

    public function testAccessPermsMemberFullAccess(): void
    {
        $t_access_members = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_members->method('getId')->willReturn(3);
        $t_access_members->method('getGroupId')->willReturn(101);
        $t_access_members->method('getProject')->willReturn($this->project);
        $perms = [
            3 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_members->method('getPermissionsByUgroupId')->willReturn($perms);
        $t_access_members->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_members->method('getUserManager')->willReturn($this->user_manager);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_members));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->registered, $t_access_members));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_member, $t_access_members));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_members));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_members));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_members));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_members));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_members));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_members));
    }

    public function testAccessPermsTrackerAdminAllProjects(): void
    {
        $t_access_members = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_members->method('getId')->willReturn(3);
        $t_access_members->method('getGroupId')->willReturn(101);
        $t_access_members->method('getProject')->willReturn($this->project);
        $perms = [];
        $t_access_members->method('getPermissionsByUgroupId')->willReturn($perms);

        $global_admin_permissions_checker = $this->createMock(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class);
        $t_access_members->method('getGlobalAdminPermissionsChecker')->willReturn($global_admin_permissions_checker);
        $t_access_members->method('getUserManager')->willReturn($this->user_manager);

        $global_admin_permissions_checker
            ->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->all_trackers_forge_admin_user)
            ->willReturn(true);

        $permission_checker = new Tracker_Permission_PermissionChecker(
            $this->user_manager,
            \Tuleap\Test\Stubs\CheckProjectAccessStub::withPrivateProjectWithoutAccess(),
            $global_admin_permissions_checker,
        );

        $this->assertTrue($permission_checker->userCanViewTracker($this->all_trackers_forge_admin_user, $t_access_members));
    }

    public function testAccessPermsAdminFullAccess(): void
    {
        $t_access_admin = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_admin->method('getId')->willReturn(4);
        $t_access_admin->method('getGroupId')->willReturn(101);
        $t_access_admin->method('getProject')->willReturn($this->project);
        $perms = [
            4 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_admin->method('getPermissionsByUgroupId')->willReturn($perms);
        $t_access_admin->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_admin->method('getUserManager')->willReturn($this->user_manager);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_admin));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->registered, $t_access_admin));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $t_access_admin));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_admin));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_admin));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_admin));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_admin));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_admin));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_admin));
    }

    public function testAccessPermsSubmitterFullAccess(): void
    {
        $t_access_submitter = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_submitter->method('getId')->willReturn(5);
        $t_access_submitter->method('getGroupId')->willReturn(101);
        $t_access_submitter->method('getProject')->willReturn($this->project);
        $perms = [
            4   => [101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            138 => [101 => 'PLUGIN_TRACKER_ACCESS_SUBMITTER'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_submitter->method('getPermissionsByUgroupId')->willReturn($perms);
        $t_access_submitter->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_submitter->method('getUserManager')->willReturn($this->user_manager);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_submitter));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->registered, $t_access_submitter));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $t_access_submitter));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_submitter));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_submitter));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_submitter));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_submitter));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_submitter));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_submitter));
    }

    public function testAccessPermsAssigneeFullAccess(): void
    {
        $t_access_assignee = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_assignee->method('getId')->willReturn(6);
        $t_access_assignee->method('getGroupId')->willReturn(101);
        $t_access_assignee->method('getProject')->willReturn($this->project);
        $perms = [
            4   => [101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            196 => [101 => 'PLUGIN_TRACKER_ACCESS_ASSIGNEE'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_assignee->method('getPermissionsByUgroupId')->willReturn($perms);
        $t_access_assignee->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_assignee->method('getUserManager')->willReturn($this->user_manager);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_assignee));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->registered, $t_access_assignee));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $t_access_assignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_assignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_assignee));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_assignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_assignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_assignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_assignee));
    }

    public function testAccessPermsSubmitterAssigneeFullAccess(): void
    {
        $t_access_submitterassignee = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_submitterassignee->method('getId')->willReturn(7);
        $t_access_submitterassignee->method('getGroupId')->willReturn(101);
        $t_access_submitterassignee->method('getProject')->willReturn($this->project);
        $t_access_submitterassignee->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_submitterassignee->method('getUserManager')->willReturn($this->user_manager);

        $perms = [
            4   => [101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            138 => [101 => 'PLUGIN_TRACKER_ACCESS_SUBMITTER'],
            196 => [101 => 'PLUGIN_TRACKER_ACCESS_ASSIGNEE'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_submitterassignee->method('getPermissionsByUgroupId')->willReturn($perms);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_submitterassignee));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->registered, $t_access_submitterassignee));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $t_access_submitterassignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_submitterassignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_submitterassignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_submitterassignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_submitterassignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_submitterassignee));
        $this->assertTrue($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_submitterassignee));
    }

    public function testAccessPermsPrivateProject(): void
    {
        $t_access_registered = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
            'getPermissionsByUgroupId',
            'getGlobalAdminPermissionsChecker',
            'getUserManager',
        ]);
        $t_access_registered->method('getId')->willReturn(7);
        $t_access_registered->method('getGroupId')->willReturn(102);
        $t_access_registered->method('getProject')->willReturn($this->project_private);

        $perms = [
            2    => [ 102 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1003 => [ 102 => 'PLUGIN_TRACKER_ADMIN'],
        ];

        $t_access_registered->method('getPermissionsByUgroupId')->willReturn($perms);
        $t_access_registered->method('getGlobalAdminPermissionsChecker')->willReturn($this->global_admin_permissions_checker);
        $t_access_registered->method('getUserManager')->willReturn($this->user_manager);

        $this->project_access_checker->method('checkUserCanAccessProject')->willReturnCallback(
            fn (PFUser $user, Project $project) => match ($user) {
                $this->super_admin => null,
                default => throw $this->createMock(Project_AccessException::class),
            }
        );

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->anonymous, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->registered, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_admin, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitter, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_assignee, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_submitterassignee, $t_access_registered));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->tracker_admin, $t_access_registered));

        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $t_access_registered));
    }

    public function testTrackerInNotActiveProjectIsOnlyReadableBySuperAdmin(): void
    {
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withId(42)->build();

        $tracker = $this->createPartialMock(\Tuleap\Tracker\Tracker::class, [
            'getId',
            'getGroupId',
            'getProject',
        ]);
        $tracker->method('getId')->willReturn(7);
        $tracker->method('getGroupId')->willReturn(102);
        $tracker->method('getProject')->willReturn($project);


        $this->project_access_checker->method('checkUserCanAccessProject')->willReturnCallback(
            fn (PFUser $user, Project $project) => match ($user) {
                $this->super_admin => null,
                default => throw $this->createMock(Project_AccessException::class),
            }
        );

        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $tracker));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_admin, $tracker));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $tracker));
    }

    public function testArtifactAccessForUserNotAllowedToAccessToProject(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject')->willThrowException($this->createMock(Project_AccessException::class));

        $this->restricted->method('isMember')->willReturn(true);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($this->tracker);

        $this->assertFalse($this->permission_checker->userCanView($this->restricted, $artifact));
    }

    public function testUserCanViewTrackerAccessSubmitter(): void
    {
        $ugroup_sub = 102;

        // $artifact_submitter has been submitted by $submitter and assigned to $u
        // $submitter, $u_sub should have the right to see it.
        // $other, $assignee, $u_ass and $u should not have the right to see it

        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('userIsAdmin')->willReturn(false);
        $this->submitter->method('isAdmin')->willReturn(false);
        $this->u_sub->method('isAdmin')->willReturn(false);
        $this->other->method('isAdmin')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->assignee->method('isAdmin')->willReturn(false);
        $this->u_ass->method('isAdmin')->willReturn(false);

        $permissions = ['PLUGIN_TRACKER_ACCESS_SUBMITTER' => [0 => $ugroup_sub]];
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn($permissions);

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->method('useArtifactPermissions')->willReturn(false);
        $artifact->method('getSubmittedBy')->willReturn(123);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $artifact));
        $this->assertTrue($this->permission_checker->userCanView($this->u_sub, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->assignee, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->u_ass, $artifact));
    }

    public function testUserCanViewWhenTrackerIsDeleted(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->tracker->method('isDeleted')->willReturn(true);

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getTracker')->willReturn($this->tracker);

        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->user, $this->tracker));
    }

    public function testUserCanViewTrackerAccessAssignee(): void
    {
        $ugroup_ass = 101;

        // $artifact_assignee has been submitted by $u and assigned to $assignee
        // $assignee and $u_ass should have the right to see it.
        // $other, $submitter, $u_sub and $u should not have the right to see it
        $permissions = ['PLUGIN_TRACKER_ACCESS_ASSIGNEE' => [0 => $ugroup_ass]];
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn($permissions);

        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('userIsAdmin')->willReturn(false);
        $this->submitter->method('isAdmin')->willReturn(false);
        $this->u_sub->method('isAdmin')->willReturn(false);
        $this->other->method('isAdmin')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->assignee->method('isAdmin')->willReturn(false);
        $this->u_ass->method('isAdmin')->willReturn(false);

        $contributor_field = $this->createMock(StringField::class);
        $this->tracker->method('getContributorField')->willReturn($contributor_field);
        $artifact_assignee = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_assignee->method('getTracker')->willReturn($this->tracker);
        $artifact_assignee->method('useArtifactPermissions')->willReturn(false);
        $artifact_assignee->method('getSubmittedBy')->willReturn(120);
        $user_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $contributors         = [121];
        $user_changeset_value->method('getValue')->willReturn($contributors);
        $artifact_assignee->method('getValue')->with($contributor_field)->willReturn($user_changeset_value);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertTrue($this->permission_checker->userCanView($this->assignee, $artifact_assignee));
        $this->assertTrue($this->permission_checker->userCanView($this->u_ass, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->submitter, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->u_sub, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact_assignee));
    }

    public function testUserCanViewTrackerAccessSubmitterOrAssignee(): void
    {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $assignee, $u_ass, $submitter, $u_sub should have the right to see it.
        // $other and $u should not have the right to see it
        $permissions = ['PLUGIN_TRACKER_ACCESS_ASSIGNEE'  => [0 => $ugroup_ass],
            'PLUGIN_TRACKER_ACCESS_SUBMITTER' => [0 => $ugroup_sub],
        ];
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn($permissions);

        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('userIsAdmin')->willReturn(false);
        $this->submitter->method('isAdmin')->willReturn(false);
        $this->u_sub->method('isAdmin')->willReturn(false);
        $this->other->method('isAdmin')->willReturn(false);
        $this->user->method('isAdmin')->willReturn(false);
        $this->assignee->method('isAdmin')->willReturn(false);
        $this->u_ass->method('isAdmin')->willReturn(false);

        $contributor_field = $this->createMock(StringField::class);
        $this->tracker->method('getContributorField')->willReturn($contributor_field);
        $artifact_subass = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_subass->method('getTracker')->willReturn($this->tracker);
        $artifact_subass->method('useArtifactPermissions')->willReturn(false);
        $artifact_subass->method('getSubmittedBy')->willReturn(123);
        $user_changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $contributors         = [121];
        $user_changeset_value->method('getValue')->willReturn($contributors);
        $artifact_subass->method('getValue')->with($contributor_field)->willReturn($user_changeset_value);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $artifact_subass));
        $this->assertTrue($this->permission_checker->userCanView($this->u_sub, $artifact_subass));
        $this->assertTrue($this->permission_checker->userCanView($this->assignee, $artifact_subass));
        $this->assertTrue($this->permission_checker->userCanView($this->u_ass, $artifact_subass));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact_subass));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact_subass));
    }

    public function testUserCanViewTrackerAccessFull(): void
    {
        $ugroup_ful = 103;

        // $assignee is in (UgroupAss - ugroup_id=101)
        // $submitter is in (UgroupSub - ugroup_id=102)
        // $u is in (UgroupFul - ugroup_id=103);
        // $other do not belong to any ugroup
        $u = $this->createMock(\PFUser::class);
        $u->method('getId')->willReturn(120);
        $u->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => false,
                102 => false,
                103 => true,
            }
        );
        $u->method('isSuperUser')->willReturn(false);
        $u->method('isAdmin')->willReturn(false);

        $assignee = $this->createMock(\PFUser::class);
        $assignee->method('getId')->willReturn(121);
        $assignee->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => true,
                102 => false,
                103 => false,
            }
        );
        $assignee->method('isSuperUser')->willReturn(false);
        $assignee->method('isAdmin')->willReturn(false);
        $submitter = $this->createMock(\PFUser::class);
        $submitter->method('getId')->willReturn(122);
        $submitter->method('isMemberOfUGroup')->willReturnCallback(
            static fn (int $ugroup_id) => match ($ugroup_id) {
                101 => false,
                102 => true,
                103 => false,
            }
        );
        $submitter->method('isSuperUser')->willReturn(false);
        $submitter->method('isAdmin')->willReturn(false);
        $other = $this->createMock(\PFUser::class);
        $other->method('getId')->willReturn(123);
        $other->method('isMemberOfUGroup')->willReturn(false);
        $other->method('isSuperUser')->willReturn(false);
        $other->method('isAdmin')->willReturn(false);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserById')->willReturnCallback(static fn (int $id) => match ($id) {
            120 => $u,
            121 => $assignee,
            122 => $submitter,
            123 => $other,
        });

        $this->project_access_checker->method('checkUserCanAccessProject');

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $u should have the right to see it.
        // $other, $submitter and assigned should not have the right to see it
        $permissions = ['PLUGIN_TRACKER_ACCESS_FULL' => [0 => $ugroup_ful]];
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn($permissions);

        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('userIsAdmin')->willReturn(false);

        $contributor_field = $this->createMock(StringField::class);
        $this->tracker->method('getContributorField')->willReturn($contributor_field);
        $artifact_subass = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_subass->method('getTracker')->willReturn($this->tracker);
        $artifact_subass->method('useArtifactPermissions')->willReturn(false);
        $artifact_subass->method('getSubmittedBy')->willReturn(123);
        $user_changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $contributors         = [121];
        $user_changeset_value->method('getValue')->willReturn($contributors);
        $artifact_subass->method('getValue')->with($contributor_field)->willReturn($user_changeset_value);

        $permission_checker = new Tracker_Permission_PermissionChecker($user_manager, $this->project_access_checker, $this->global_admin_permissions_checker);
        $this->assertFalse($permission_checker->userCanView($submitter, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($assignee, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($other, $artifact_subass));
        $this->assertTrue($permission_checker->userCanView($u, $artifact_subass));
    }
}
