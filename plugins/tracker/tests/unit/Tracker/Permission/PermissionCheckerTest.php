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

use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Tracker\Artifact\Artifact;

final class Tracker_Permission_PermissionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $restricted;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $assignee;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $u_ass;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $submitter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $u_sub;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $other;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var Tracker_Permission_PermissionChecker
     */
    private $permission_checker;
    /**
     * @var \Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $anonymous;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $registered;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $project_member;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $project_admin;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $super_admin;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $tracker_submitter;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $tracker_assignee;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $tracker_submitterassignee;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $tracker_admin;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $all_trackers_forge_admin_user;
    /**
     * @var \Mockery\MockInterface|Project
     */
    private $project_private;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker
     */
    private $global_admin_permissions_checker;

    protected function setUp(): void
    {
        $this->project = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturns(120);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('isActive')->andReturns(true);

        $this->project_private = \Mockery::spy(\Project::class);
        $this->project_private->shouldReceive('getID')->andReturns(102);
        $this->project_private->shouldReceive('isPublic')->andReturns(false);
        $this->project_private->shouldReceive('isActive')->andReturns(true);

        $this->user_manager           = \Mockery::spy(\UserManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);

        $this->global_admin_permissions_checker = \Mockery::spy(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class);
        $this->global_admin_permissions_checker->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')->andReturns(false);

        $this->permission_checker = new Tracker_Permission_PermissionChecker(
            $this->user_manager,
            $this->project_access_checker,
            $this->global_admin_permissions_checker,
        );

        // $assignee and $u_ass are in the same ugroup (UgroupAss - ugroup_id=101)
        // $submitter and $u_sub are in the same ugroup (UgroupSub - ugroup_id=102)
        // $other and $u are neither in UgroupAss nor in UgroupSub

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(120);
        $this->user->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->assignee = \Mockery::spy(\PFUser::class);
        $this->assignee->shouldReceive('getId')->andReturns(121);
        $this->assignee->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(true);
        $this->assignee->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(false);
        $this->assignee->shouldReceive('isSuperUser')->andReturns(false);
        $this->assignee->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->u_ass = \Mockery::spy(\PFUser::class);
        $this->u_ass->shouldReceive('getId')->andReturns(122);
        $this->u_ass->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(true);
        $this->u_ass->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(false);
        $this->u_ass->shouldReceive('isSuperUser')->andReturns(false);
        $this->u_ass->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->submitter = \Mockery::spy(\PFUser::class);
        $this->submitter->shouldReceive('getId')->andReturns(123);
        $this->submitter->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(false);
        $this->submitter->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(true);
        $this->submitter->shouldReceive('isSuperUser')->andReturns(false);
        $this->submitter->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->u_sub = \Mockery::spy(\PFUser::class);
        $this->u_sub->shouldReceive('getId')->andReturns(124);
        $this->u_sub->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(false);
        $this->u_sub->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(true);
        $this->u_sub->shouldReceive('isSuperUser')->andReturns(false);
        $this->u_sub->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->other = \Mockery::spy(\PFUser::class);
        $this->other->shouldReceive('getId')->andReturns(125);
        $this->other->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->other->shouldReceive('isSuperUser')->andReturns(false);
        $this->other->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->restricted = \Mockery::spy(\PFUser::class);
        $this->restricted->shouldReceive('getId')->andReturns(126);
        $this->restricted->shouldReceive('isMemberOfUGroup')->andReturns(true);
        $this->restricted->shouldReceive('isSuperUser')->andReturns(false);
        $this->restricted->shouldReceive('isRestricted')->andReturns(true);

        $this->user_manager->shouldReceive('getUserById')->with(120)->andReturns($this->user);
        $this->user_manager->shouldReceive('getUserById')->with(121)->andReturns($this->assignee);
        $this->user_manager->shouldReceive('getUserById')->with(122)->andReturns($this->u_ass);
        $this->user_manager->shouldReceive('getUserById')->with(123)->andReturns($this->submitter);
        $this->user_manager->shouldReceive('getUserById')->with(124)->andReturns($this->u_sub);
        $this->user_manager->shouldReceive('getUserById')->with(125)->andReturns($this->other);
        $this->user_manager->shouldReceive('getUserById')->with(126)->andReturns($this->restricted);

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns(666);
        $this->tracker->shouldReceive('getGroupId')->andReturns(222);
        $this->tracker->shouldReceive('getProject')->andReturns($this->project);

        $this->anonymous = \Mockery::spy(\PFUser::class);
        $this->anonymous->shouldReceive('isSuperUser')->andReturns(false);
        $this->anonymous->shouldReceive('getId')->andReturns(0);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(false);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(false);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(false);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(false);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->anonymous->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);

        $this->registered = \Mockery::spy(\PFUser::class);
        $this->registered->shouldReceive('isSuperUser')->andReturns(false);
        $this->registered->shouldReceive('getId')->andReturns(101);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(true);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(false);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(false);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(false);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->registered->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);

        $this->project_member = \Mockery::spy(\PFUser::class);
        $this->project_member->shouldReceive('isSuperUser')->andReturns(false);
        $this->project_member->shouldReceive('getId')->andReturns(102);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(true);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(true);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(false);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(false);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->project_member->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->project_member->shouldReceive('isMember')->with(102)->andReturns(false);

        $this->project_admin = \Mockery::spy(\PFUser::class);
        $this->project_admin->shouldReceive('isSuperUser')->andReturns(false);
        $this->project_admin->shouldReceive('getId')->andReturns(103);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(true);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(true);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(true);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(false);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(false);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->project_admin->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->project_admin->shouldReceive('isMember')->with(102)->andReturns(false);

        $this->super_admin = \Mockery::spy(\PFUser::class);
        $this->super_admin->shouldReceive('isSuperUser')->andReturns(true);
        $this->super_admin->shouldReceive('getId')->andReturns(104);
        $this->super_admin->shouldReceive('isMemberOfUGroup')->with(Mockery::any(), Mockery::any())->andReturns(true);
        $this->super_admin->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->super_admin->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);

        $this->tracker_submitter = \Mockery::spy(\PFUser::class);
        $this->tracker_submitter->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker_submitter->shouldReceive('getId')->andReturns(105);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(false);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(false);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(true);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(false);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->tracker_submitter->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->tracker_submitter->shouldReceive('isMember')->with(102)->andReturns(false);

        $this->tracker_assignee = \Mockery::spy(\PFUser::class);
        $this->tracker_assignee->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker_assignee->shouldReceive('getId')->andReturns(106);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(false);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(false);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(false);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(true);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->tracker_assignee->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->tracker_assignee->shouldReceive('isMember')->with(102)->andReturns(false);

        $this->tracker_submitterassignee = \Mockery::spy(\PFUser::class);
        $this->tracker_submitterassignee->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker_submitterassignee->shouldReceive('getId')->andReturns(107);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(true);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(false);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(false);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(true);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(true);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(false);
        $this->tracker_submitterassignee->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->tracker_submitterassignee->shouldReceive('isMember')->with(102)->andReturns(false);

        $this->tracker_admin = \Mockery::spy(\PFUser::class);
        $this->tracker_admin->shouldReceive('isSuperUser')->andReturns(false);
        $this->tracker_admin->shouldReceive('getId')->andReturns(107);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(1, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(2, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(3, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(4, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(138, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(196, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(1001, Mockery::any())->andReturns(true);
        $this->tracker_admin->shouldReceive('isMemberOfUGroup')->with(1002, Mockery::any())->andReturns(false);
        $this->tracker_admin->shouldReceive('isMember')->with(102)->andReturns(false);

        $this->all_trackers_forge_admin_user = \Mockery::spy(\PFUser::class);
        $this->all_trackers_forge_admin_user->shouldReceive('getId')->andReturns(888);
        $this->all_trackers_forge_admin_user->shouldReceive('isMember')->andReturns(false);
        $this->all_trackers_forge_admin_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->all_trackers_forge_admin_user->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->all_trackers_forge_admin_user->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->all_trackers_forge_admin_user->shouldReceive('isLoggedIn')->andReturns(true);
    }

    public function testTrackerAccessForUserNotAllowedToAccessToProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(Mockery::mock(Project_AccessException::class));

        $this->restricted->shouldReceive('isMember')->andReturn(true);

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->restricted, $this->tracker));
    }

    public function testAccessPermsAnonymousFullAccess(): void
    {
        $t_access_anonymous = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        \assert($t_access_anonymous instanceof Tracker);
        $t_access_anonymous->shouldReceive('getId')->andReturns(1);
        $t_access_anonymous->shouldReceive('getGroupId')->andReturns(101);
        $t_access_anonymous->shouldReceive('getProject')->andReturns($this->project);
        $perms = [
            1 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_anonymous->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_anonymous->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_anonymous->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_registered = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_registered->shouldReceive('getId')->andReturns(2);
        $t_access_registered->shouldReceive('getGroupId')->andReturns(101);
        $t_access_registered->shouldReceive('getProject')->andReturns($this->project);
        $t_access_registered->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_registered->shouldReceive('getUserManager')->andReturns($this->user_manager);
        $perms = [
            2 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_registered->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_members = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_members->shouldReceive('getId')->andReturns(3);
        $t_access_members->shouldReceive('getGroupId')->andReturns(101);
        $t_access_members->shouldReceive('getProject')->andReturns($this->project);
        $perms = [
            3 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_members->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_members->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_members->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_members = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_members->shouldReceive('getId')->andReturns(3);
        $t_access_members->shouldReceive('getGroupId')->andReturns(101);
        $t_access_members->shouldReceive('getProject')->andReturns($this->project);
        $perms = [];
        $t_access_members->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);

        $global_admin_permissions_checker = \Mockery::spy(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class);
        $t_access_members->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($global_admin_permissions_checker);
        $t_access_members->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $global_admin_permissions_checker
            ->shouldReceive('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($this->project, $this->all_trackers_forge_admin_user)
            ->andReturns(true);

        $permission_checker = new Tracker_Permission_PermissionChecker(
            $this->user_manager,
            \Tuleap\Test\Stubs\CheckProjectAccessStub::withPrivateProjectWithoutAccess(),
            $global_admin_permissions_checker,
        );

        $this->assertTrue($permission_checker->userCanViewTracker($this->all_trackers_forge_admin_user, $t_access_members));
    }

    public function testAccessPermsAdminFullAccess(): void
    {
        $t_access_admin = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_admin->shouldReceive('getId')->andReturns(4);
        $t_access_admin->shouldReceive('getGroupId')->andReturns(101);
        $t_access_admin->shouldReceive('getProject')->andReturns($this->project);
        $perms = [
            4 => [ 101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_admin->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_admin->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_admin->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_submitter = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_submitter->shouldReceive('getId')->andReturns(5);
        $t_access_submitter->shouldReceive('getGroupId')->andReturns(101);
        $t_access_submitter->shouldReceive('getProject')->andReturns($this->project);
        $perms = [
            4   => [101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            138 => [101 => 'PLUGIN_TRACKER_ACCESS_SUBMITTER'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_submitter->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_submitter->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_submitter->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_assignee = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_assignee->shouldReceive('getId')->andReturns(6);
        $t_access_assignee->shouldReceive('getGroupId')->andReturns(101);
        $t_access_assignee->shouldReceive('getProject')->andReturns($this->project);
        $perms = [
            4   => [101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            196 => [101 => 'PLUGIN_TRACKER_ACCESS_ASSIGNEE'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_assignee->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_assignee->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_assignee->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_submitterassignee = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_submitterassignee->shouldReceive('getId')->andReturns(7);
        $t_access_submitterassignee->shouldReceive('getGroupId')->andReturns(101);
        $t_access_submitterassignee->shouldReceive('getProject')->andReturns($this->project);
        $t_access_submitterassignee->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_submitterassignee->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $perms = [
            4   => [101 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            138 => [101 => 'PLUGIN_TRACKER_ACCESS_SUBMITTER'],
            196 => [101 => 'PLUGIN_TRACKER_ACCESS_ASSIGNEE'],
            1001 => [ 101 => 'PLUGIN_TRACKER_ADMIN'],
        ];
        $t_access_submitterassignee->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $t_access_registered = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_registered->shouldReceive('getId')->andReturns(7);
        $t_access_registered->shouldReceive('getGroupId')->andReturns(102);
        $t_access_registered->shouldReceive('getProject')->andReturns($this->project_private);

        $perms = [
            2    => [ 102 => 'PLUGIN_TRACKER_ACCESS_FULL'],
            1003 => [ 102 => 'PLUGIN_TRACKER_ADMIN'],
        ];

        $t_access_registered->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_registered->shouldReceive('getGlobalAdminPermissionsChecker')->andReturns($this->global_admin_permissions_checker);
        $t_access_registered->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->anonymous, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->registered, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->project_member, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->project_admin, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->tracker_submitter, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->tracker_assignee, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->tracker_submitterassignee, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->tracker_admin, $this->project_private)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->super_admin, $this->project_private);

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
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(42);
        $project->shouldReceive('isActive')->andReturns(false);

        $tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tracker->shouldReceive('getId')->andReturns(7);
        $tracker->shouldReceive('getGroupId')->andReturns(102);
        $tracker->shouldReceive('getProject')->andReturns($project);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->project_member, $project)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->project_admin, $project)->andThrow(Mockery::mock(Project_AccessException::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->with($this->super_admin, $project);

        $this->assertTrue($this->permission_checker->userCanViewTracker($this->super_admin, $tracker));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_admin, $tracker));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->project_member, $tracker));
    }

    public function testArtifactAccessForUserNotAllowedToAccessToProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(Mockery::mock(Project_AccessException::class));

        $this->restricted->shouldReceive('isMember')->andReturn(true);

        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->assertFalse($this->permission_checker->userCanView($this->restricted, $artifact));
    }

    public function testUserCanViewTrackerAccessSubmitter(): void
    {
        $ugroup_sub = 102;

        // $artifact_submitter has been submitted by $submitter and assigned to $u
        // $submitter, $u_sub should have the right to see it.
        // $other, $assignee, $u_ass and $u should not have the right to see it

        $permissions = ["PLUGIN_TRACKER_ACCESS_SUBMITTER" => [0 => $ugroup_sub]];
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact->shouldReceive('getSubmittedBy')->andReturns(123);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $artifact));
        $this->assertTrue($this->permission_checker->userCanView($this->u_sub, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->assignee, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->u_ass, $artifact));
    }

    public function testUserCanViewWhenTrackerIsDeleted(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);
        $this->tracker->shouldReceive('isDeleted')->AndReturn(true);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact));
        $this->assertFalse($this->permission_checker->userCanViewTracker($this->user, $this->tracker));
    }

    public function testUserCanViewTrackerAccessAssignee(): void
    {
        $ugroup_ass = 101;

        // $artifact_assignee has been submitted by $u and assigned to $assignee
        // $assignee and $u_ass should have the right to see it.
        // $other, $submitter, $u_sub and $u should not have the right to see it
        $permissions = ["PLUGIN_TRACKER_ACCESS_ASSIGNEE" => [0 => $ugroup_ass]];
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $contributor_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $this->tracker->shouldReceive('getContributorField')->andReturns($contributor_field);
        $artifact_assignee = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_assignee->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact_assignee->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact_assignee->shouldReceive('getSubmittedBy')->andReturns(120);
        $user_changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $contributors         = [121];
        $user_changeset_value->shouldReceive('getValue')->andReturns($contributors);
        $artifact_assignee->shouldReceive('getValue')->with($contributor_field)->andReturns($user_changeset_value);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $permissions = ["PLUGIN_TRACKER_ACCESS_ASSIGNEE"  => [0 => $ugroup_ass],
            "PLUGIN_TRACKER_ACCESS_SUBMITTER" => [0 => $ugroup_sub],
        ];
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $contributor_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $this->tracker->shouldReceive('getContributorField')->andReturns($contributor_field);
        $artifact_subass = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_subass->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact_subass->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact_subass->shouldReceive('getSubmittedBy')->andReturns(123);
        $user_changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue::class);
        $contributors         = [121];
        $user_changeset_value->shouldReceive('getValue')->andReturns($contributors);
        $artifact_subass->shouldReceive('getValue')->with($contributor_field)->andReturns($user_changeset_value);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

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
        $u = \Mockery::spy(\PFUser::class);
        $u->shouldReceive('getId')->andReturns(120);
        $u->shouldReceive('isMemberOfUGroup')->with(103, 222)->andReturns(true);
        $u->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(false);
        $u->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(false);
        $u->shouldReceive('isSuperUser')->andReturns(false);

        $assignee = \Mockery::spy(\PFUser::class);
        $assignee->shouldReceive('getId')->andReturns(121);
        $assignee->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(true);
        $assignee->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(false);
        $assignee->shouldReceive('isMemberOfUGroup')->with(103, 222)->andReturns(false);
        $assignee->shouldReceive('isSuperUser')->andReturns(false);
        $submitter = \Mockery::spy(\PFUser::class);
        $submitter->shouldReceive('getId')->andReturns(122);
        $submitter->shouldReceive('isMemberOfUGroup')->with(101, 222)->andReturns(false);
        $submitter->shouldReceive('isMemberOfUGroup')->with(102, 222)->andReturns(true);
        $submitter->shouldReceive('isMemberOfUGroup')->with(103, 222)->andReturns(false);
        $submitter->shouldReceive('isSuperUser')->andReturns(false);
        $other = \Mockery::spy(\PFUser::class);
        $other->shouldReceive('getId')->andReturns(123);
        $other->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $other->shouldReceive('isSuperUser')->andReturns(false);

        $user_manager = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('getUserById')->with(120)->andReturns($u);
        $user_manager->shouldReceive('getUserById')->with(121)->andReturns($assignee);
        $user_manager->shouldReceive('getUserById')->with(122)->andReturns($submitter);
        $user_manager->shouldReceive('getUserById')->with(123)->andReturns($other);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $u should have the right to see it.
        // $other, $submitter and assigned should not have the right to see it
        $permissions = ["PLUGIN_TRACKER_ACCESS_FULL" => [0 => $ugroup_ful]];
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $contributor_field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $this->tracker->shouldReceive('getContributorField')->andReturns($contributor_field);
        $artifact_subass = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_subass->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact_subass->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact_subass->shouldReceive('getSubmittedBy')->andReturns(123);
        $user_changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue::class);
        $contributors         = [121];
        $user_changeset_value->shouldReceive('getValue')->andReturns($contributors);
        $artifact_subass->shouldReceive('getValue')->with($contributor_field)->andReturns($user_changeset_value);

        $permission_checker = new Tracker_Permission_PermissionChecker($user_manager, $this->project_access_checker, $this->global_admin_permissions_checker);
        $this->assertFalse($permission_checker->userCanView($submitter, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($assignee, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($other, $artifact_subass));
        $this->assertTrue($permission_checker->userCanView($u, $artifact_subass));
    }
}
