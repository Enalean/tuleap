<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All Rights Reserved.
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

use Tuleap\Project\ProjectAccessChecker;

require_once __DIR__.'/../../bootstrap.php';

class Tracker_Permission_PermissionCheckerTest extends TuleapTestCase
{
    private $user_manager;

    private $user;
    private $assignee;
    private $u_ass;
    private $submitter;
    private $u_sub;
    private $other;

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
     * @var \Mockery\MockInterface|TrackerManager
     */
    private $tracker_manager;
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

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getID()->returns(120);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->project_private = \Mockery::spy(\Project::class);
        $this->project_private->shouldReceive('getID')->andReturns(102);
        $this->project_private->shouldReceive('isPublic')->andReturns(false);
        $this->project_private->shouldReceive('isActive')->andReturns(true);

        $this->user_manager           = \Mockery::spy(\UserManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);

        $this->permission_checker = new Tracker_Permission_PermissionChecker($this->user_manager, $this->project_access_checker);

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

        $this->tracker_manager = \Mockery::spy(\TrackerManager::class);
        $this->tracker_manager->shouldReceive('userCanAdminAllProjectTrackers')->andReturns(false);

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

    public function testTrackerAccessForUserNotAllowedToAccessToProject()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(Mockery::mock(Project_AccessException::class));

        $this->restricted->shouldReceive('isMember')->andReturn(true);

        $this->assertFalse($this->permission_checker->userCanViewTracker($this->restricted, $this->tracker));
    }

    public function testAccessPermsAnonymousFullAccess()
    {
        /** @var Tracker $t_access_anonymous */
        $t_access_anonymous = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_anonymous->shouldReceive('getId')->andReturns(1);
        $t_access_anonymous->shouldReceive('getGroupId')->andReturns(101);
        $t_access_anonymous->shouldReceive('getProject')->andReturns($this->project);
        $perms = array(
            1 => array( 101 => 'PLUGIN_TRACKER_ACCESS_FULL'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
        $t_access_anonymous->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_anonymous->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
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

    public function testAccessPermsRegisteredFullAccess()
    {
        $t_access_registered = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_registered->shouldReceive('getId')->andReturns(2);
        $t_access_registered->shouldReceive('getGroupId')->andReturns(101);
        $t_access_registered->shouldReceive('getProject')->andReturns($this->project);
        $t_access_registered->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
        $t_access_registered->shouldReceive('getUserManager')->andReturns($this->user_manager);
        $perms = array(
            2 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
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

    public function testAccessPermsMemberFullAccess()
    {
        $t_access_members = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_members->shouldReceive('getId')->andReturns(3);
        $t_access_members->shouldReceive('getGroupId')->andReturns(101);
        $t_access_members->shouldReceive('getProject')->andReturns($this->project);
        $perms = array(
            3 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
        $t_access_members->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_members->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
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

    public function testAccessPermsTrackerAdminAllProjects()
    {
        $t_access_members = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_members->shouldReceive('getId')->andReturns(3);
        $t_access_members->shouldReceive('getGroupId')->andReturns(101);
        $t_access_members->shouldReceive('getProject')->andReturns($this->project);
        $perms = array(
            3 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
        $t_access_members->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);

        $tracker_manager = \Mockery::spy(\TrackerManager::class);
        $t_access_members->shouldReceive('getTrackerManager')->andReturns($tracker_manager);
        $t_access_members->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andReturn(true);

        stub($tracker_manager)->userCanAdminAllProjectTrackers($this->all_trackers_forge_admin_user)->returns(true);

        $this->assertTrue($this->permission_checker->userCanViewTracker($this->all_trackers_forge_admin_user, $t_access_members));
    }

    public function testAccessPermsAdminFullAccess()
    {
        $t_access_admin = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_admin->shouldReceive('getId')->andReturns(4);
        $t_access_admin->shouldReceive('getGroupId')->andReturns(101);
        $t_access_admin->shouldReceive('getProject')->andReturns($this->project);
        $perms = array(
            4 => array( 101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
        $t_access_admin->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_admin->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
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

    public function testAccessPermsSubmitterFullAccess()
    {
        $t_access_submitter = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_submitter->shouldReceive('getId')->andReturns(5);
        $t_access_submitter->shouldReceive('getGroupId')->andReturns(101);
        $t_access_submitter->shouldReceive('getProject')->andReturns($this->project);
        $perms = array(
            4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            138 => array(101=>'PLUGIN_TRACKER_ACCESS_SUBMITTER'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
        $t_access_submitter->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_submitter->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
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

    public function testAccessPermsAssigneeFullAccess()
    {
        $t_access_assignee = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_assignee->shouldReceive('getId')->andReturns(6);
        $t_access_assignee->shouldReceive('getGroupId')->andReturns(101);
        $t_access_assignee->shouldReceive('getProject')->andReturns($this->project);
        $perms = array(
            4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            196 => array(101=>'PLUGIN_TRACKER_ACCESS_ASSIGNEE'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
        $t_access_assignee->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_assignee->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
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

    public function testAccessPermsSubmitterAssigneeFullAccess()
    {
        $t_access_submitterassignee  = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_submitterassignee->shouldReceive('getId')->andReturns(7);
        $t_access_submitterassignee->shouldReceive('getGroupId')->andReturns(101);
        $t_access_submitterassignee->shouldReceive('getProject')->andReturns($this->project);
        $t_access_submitterassignee->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
        $t_access_submitterassignee->shouldReceive('getUserManager')->andReturns($this->user_manager);

        $perms = array(
            4   => array(101=>'PLUGIN_TRACKER_ACCESS_FULL'),
            138 => array(101=>'PLUGIN_TRACKER_ACCESS_SUBMITTER'),
            196 => array(101=>'PLUGIN_TRACKER_ACCESS_ASSIGNEE'),
            1001 => array( 101 => 'PLUGIN_TRACKER_ADMIN'),
        );
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

    public function testAccessPermsPrivateProject()
    {
        $t_access_registered  = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $t_access_registered->shouldReceive('getId')->andReturns(7);
        $t_access_registered->shouldReceive('getGroupId')->andReturns(102);
        $t_access_registered->shouldReceive('getProject')->andReturns($this->project_private);

        $perms = array(
            2    => array( 102 => 'PLUGIN_TRACKER_ACCESS_FULL'),
            1003 => array( 102 => 'PLUGIN_TRACKER_ADMIN'),
        );

        $t_access_registered->shouldReceive('getPermissionsByUgroupId')->andReturns($perms);
        $t_access_registered->shouldReceive('getTrackerManager')->andReturns($this->tracker_manager);
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

    public function testTrackerInNotActiveProjectIsOnlyReadableBySuperAdmin()
    {
        $project = Mockery::mock(Project::class);
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

    public function testArtifactAccessForUserNotAllowedToAccessToProject()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrow(Mockery::mock(Project_AccessException::class));

        $this->restricted->shouldReceive('isMember')->andReturn(true);

        $artifact = \Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->assertFalse($this->permission_checker->userCanView($this->restricted, $artifact));
    }

    function testUserCanViewTrackerAccessSubmitter()
    {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $artifact_submitter has been submitted by $submitter and assigned to $u
        // $submitter, $u_sub should have the right to see it.
        // $other, $assignee, $u_ass and $u should not have the right to see it

        $permissions = array("PLUGIN_TRACKER_ACCESS_SUBMITTER" => array(0 => $ugroup_sub));
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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

    function testUserCanViewTrackerAccessAssignee()
    {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $artifact_assignee has been submitted by $u and assigned to $assignee
        // $assignee and $u_ass should have the right to see it.
        // $other, $submitter, $u_sub and $u should not have the right to see it
        $permissions = array("PLUGIN_TRACKER_ACCESS_ASSIGNEE" => array(0 => $ugroup_ass));
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $contributor_field = aMockField()->build();
        $this->tracker->shouldReceive('getContributorField')->andReturns($contributor_field);
        $artifact_assignee = \Mockery::spy(\Tracker_Artifact::class);
        $artifact_assignee->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact_assignee->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact_assignee->shouldReceive('getSubmittedBy')->andReturns(120);
        $user_changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $contributors = array(121);
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

    function testUserCanViewTrackerAccessSubmitterOrAssignee()
    {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $assignee, $u_ass, $submitter, $u_sub should have the right to see it.
        // $other and $u should not have the right to see it
        $permissions = array("PLUGIN_TRACKER_ACCESS_ASSIGNEE"  => array(0 => $ugroup_ass),
                             "PLUGIN_TRACKER_ACCESS_SUBMITTER" => array(0 => $ugroup_sub)
                            );
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $contributor_field = aMockField()->build();
        $this->tracker->shouldReceive('getContributorField')->andReturns($contributor_field);
        $artifact_subass = \Mockery::spy(\Tracker_Artifact::class);
        $artifact_subass->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact_subass->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact_subass->shouldReceive('getSubmittedBy')->andReturns(123);
        $user_changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue::class);
        $contributors = array(121);
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

    function testUserCanViewTrackerAccessFull()
    {
        $ugroup_ass = 101;
        $ugroup_sub = 102;
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
        $permissions = array("PLUGIN_TRACKER_ACCESS_FULL" => array(0 => $ugroup_ful));
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns($permissions);

        $contributor_field = aMockField()->build();
        $this->tracker->shouldReceive('getContributorField')->andReturns($contributor_field);
        $artifact_subass = \Mockery::spy(\Tracker_Artifact::class);
        $artifact_subass->shouldReceive('getTracker')->andReturns($this->tracker);
        $artifact_subass->shouldReceive('useArtifactPermissions')->andReturns(false);
        $artifact_subass->shouldReceive('getSubmittedBy')->andReturns(123);
        $user_changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue::class);
        $contributors = array(121);
        $user_changeset_value->shouldReceive('getValue')->andReturns($contributors);
        $artifact_subass->shouldReceive('getValue')->with($contributor_field)->andReturns($user_changeset_value);

        $permission_checker = new Tracker_Permission_PermissionChecker($user_manager, $this->project_access_checker);
        $this->assertFalse($permission_checker->userCanView($submitter, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($assignee, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($other, $artifact_subass));
        $this->assertTrue($permission_checker->userCanView($u, $artifact_subass));
    }
}

abstract class Tracker_Permission_PermissionChecker_SubmitterOnlyBaseTest extends TuleapTestCase
{
    protected $user_manager;

    protected $tracker;

    protected $user;
    protected $submitter;
    protected $ugroup_id_submitter_only;
    protected $artifact;
    /**
     * @var \Mockery\MockInterface|ProjectAccessChecker
     */
    protected $project_access_checker;
    /**
     * @var Tracker_Permission_PermissionChecker
     */
    protected $permission_checker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $project = \Mockery::spy(\Project::class);
        stub($project)->getID()->returns(120);
        stub($project)->isPublic()->returns(true);

        $this->user_manager           = \Mockery::spy(\UserManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);
        $this->permission_checker     = new Tracker_Permission_PermissionChecker($this->user_manager, $this->project_access_checker);

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns(666);
        $this->tracker->shouldReceive('getGroupId')->andReturns(222);
        $this->tracker->shouldReceive('getProject')->andReturns($project);

        $this->ugroup_id_submitter_only = 112;

        $this->user = \Mockery::spy(\PFUser::class);
        stub($this->user)->getId()->returns(120);
        $this->user->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->submitter = \Mockery::spy(\PFUser::class);
        stub($this->submitter)->getId()->returns(250);
        stub($this->submitter)->isMemberOfUGroup($this->ugroup_id_submitter_only, 222)->returns(true);
        $this->submitter->shouldReceive('isMember')->with(12)->andReturns(true);

        stub($this->user_manager)->getUserById(120)->returns($this->user);
        stub($this->user_manager)->getUserById(250)->returns($this->submitter);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->artifact)->getTracker()->returns($this->tracker);
    }
}

class Tracker_Permission_PermissionChecker_SubmitterOnlyTest extends Tracker_Permission_PermissionChecker_SubmitterOnlyBaseTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER_ONLY => array(
                    0 => $this->ugroup_id_submitter_only
                )
            )
        );

        stub($this->artifact)->getSubmittedBy()->returns(250);
    }

    public function itDoesntSeeArtifactSubmittedByOthers()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->assertFalse($this->permission_checker->userCanView($this->user, $this->artifact));
    }

    public function itSeesArtifactSubmittedByThemselves()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $this->artifact));
    }
}

class Tracker_Permission_PermissionChecker_SubmitterOnlyAndAdminTest extends Tracker_Permission_PermissionChecker_SubmitterOnlyBaseTest
{
    protected $ugroup_id_maintainers  = 111;
    protected $ugroup_id_admin        = 4;
    protected $ugroup_private_project = 114;

    protected $maintainer;
    protected $tracker_admin;
    protected $project_admin;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER_ONLY => array(
                    $this->ugroup_id_submitter_only
                ),
                Tracker::PERMISSION_FULL => array(
                    $this->ugroup_id_maintainers
                ),
                Tracker::PERMISSION_ADMIN => array(
                    $this->ugroup_id_admin
                )
            )
        );

        $this->restricted_user = \Mockery::spy(\PFUser::class);
        $this->restricted_user->shouldReceive('getId')->andReturns(249);
        $this->restricted_user->shouldReceive('isMemberOfUGroup')->with(114, 223)->andReturns(true);
        $this->restricted_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->restricted_user->shouldReceive('isMember')->with(223)->andReturns(true);
        $this->restricted_user->shouldReceive('isMember')->with(222)->andReturns(false);
        $this->restricted_user->shouldReceive('isRestricted')->andReturns(true);

        $this->not_member = \Mockery::spy(\PFUser::class);
        $this->not_member->shouldReceive('getId')->andReturns(250);
        $this->not_member->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->not_member->shouldReceive('isSuperUser')->andReturns(false);
        $this->not_member->shouldReceive('isMember')->andReturns(false);
        $this->not_member->shouldReceive('isRestricted')->andReturns(false);

        $this->maintainer = \Mockery::spy(\PFUser::class);
        stub($this->maintainer)->getId()->returns(251);
        stub($this->maintainer)->isMemberOfUGroup($this->ugroup_id_maintainers, 222)->returns(true);

        $this->tracker_admin = \Mockery::spy(\PFUser::class);
        stub($this->tracker)->userIsAdmin($this->tracker_admin)->returns(true);

        $this->project_admin = \Mockery::spy(\PFUser::class);
        stub($this->project_admin)->getId()->returns(253);
        stub($this->project_admin)->isAdmin(120)->returns(true);

        stub($this->artifact)->getSubmittedBy()->returns(250);

        $private_project            = mockery_stub(\Project::class)->isPublic()->returns(false);
        $tracker_in_private_project = mockery_stub(\Tracker::class)->getProject()->returns($private_project);

        stub($private_project)->getID()->returns(223);
        stub($tracker_in_private_project)->getGroupId()->returns(223);
        stub($tracker_in_private_project)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array(
                    $this->ugroup_private_project
                )
            )
        );

        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');

        $this->artifact2 = mockery_stub(\Tracker_Artifact::class)->getTracker()->returns($tracker_in_private_project);
    }

    public function itDoesntSeeArtifactSubmittedByOthers()
    {
        $this->assertFalse($this->permission_checker->userCanView($this->user, $this->artifact));
    }

    public function itSeesArtifactSubmittedByThemselves()
    {
        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $this->artifact));
    }

    public function itSeesArtifactBecauseHeIsGrantedFullAccess()
    {
        $this->assertTrue($this->permission_checker->userCanView($this->maintainer, $this->artifact));
    }

    public function itSeesArtifactBecauseHeIsTrackerAdmin()
    {
        $this->assertTrue($this->permission_checker->userCanView($this->tracker_admin, $this->artifact));
    }

    public function itSeesArtifactBecauseHeIsProjectAdmin()
    {
        $this->assertTrue($this->permission_checker->userCanView($this->project_admin, $this->artifact));
    }

    public function itDoesNotSeeArtifactBecauseHeIsRestricted()
    {
        $this->assertFalse($this->permission_checker->userCanView($this->restricted_user, $this->artifact));
    }

    public function itSeesTheArtifactBecauseHeIsRestrictedAndProjectMember()
    {
        $this->assertTrue($this->permission_checker->userCanView($this->restricted_user, $this->artifact2));
    }

    public function itDoesNotSeeArtifactBecauseHeIsNotProjectMemberOfAPrivateProject()
    {
        $this->assertFalse($this->permission_checker->userCanView($this->not_member, $this->artifact2));
    }
}
