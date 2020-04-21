<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Tracker;

use Mockery as M;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tracker;
use Tuleap\Tracker\PermissionsFunctionsWrapper;

final class PermissionsRepresentationBuilderTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var M\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var M\MockInterface|PermissionsFunctionsWrapper
     */
    private $permissions_functions_wrapper;
    /**
     * @var PermissionsRepresentationBuilder
     */
    private $builder;
    /**
     * @var M\MockInterface|\PFUser
     */
    private $tracker_admin_user;
    /**
     * @var M\MockInterface|\Project
     */
    private $project;
    /**
     * @var M\MockInterface|Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->tracker_admin_user = M::mock(\PFUser::class);

        $this->project = M::mock(\Project::class, ['getID' => 202]);

        $this->tracker = M::mock(Tracker::class, ['getID' => 12, 'getProject' => $this->project]);
        $this->tracker->shouldReceive('userIsAdmin')->with($this->tracker_admin_user)->andReturnTrue();

        $this->ugroup_manager = M::mock(\UGroupManager::class);

        $project_members_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::PROJECT_MEMBERS)->andReturn($project_members_ugroup);

        $this->permissions_functions_wrapper = M::mock(PermissionsFunctionsWrapper::class);
        $this->builder = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
    }

    public function testItReturnsNullWhenUserIsNotAdmin(): void
    {
        $a_random_user = M::mock(\PFUser::class);

        $this->tracker->shouldReceive('userIsAdmin')->with($a_random_user)->andReturnFalse();

        $this->assertNull($this->builder->getPermissionsRepresentation($this->tracker, $a_random_user));
    }


    public function testItReturnsAnEmptyRepresentationWhenThereAreNoPermissions(): void
    {
        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([]);
        $this->assertEquals(new PermissionsRepresentation(), $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user));
    }

    public function testItReturnsAGroupThatHaveAccess(): void
    {
        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS
                ],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1,
                ]
            ]
        ]);

        $representation = $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_admin);
        $this->assertEmpty($representation->can_access_submitted_by_group);
        $this->assertEmpty($representation->can_access_assigned_to_group);
        $this->assertEmpty($representation->can_access_submitted_by_user);
        $this->assertCount(1, $representation->can_access);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveAdminAccess(): void
    {
        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS
                ],
                'permissions' => [
                    Tracker::PERMISSION_ADMIN => 1,
                ]
            ]
        ]);

        $representation = $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_access);
        $this->assertEmpty($representation->can_access_submitted_by_group);
        $this->assertEmpty($representation->can_access_assigned_to_group);
        $this->assertEmpty($representation->can_access_submitted_by_user);
        $this->assertCount(1, $representation->can_admin);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_admin[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveSubmittedByThemAccess(): void
    {
        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS
                ],
                'permissions' => [
                    Tracker::PERMISSION_SUBMITTER_ONLY => 1,
                ]
            ]
        ]);

        $representation = $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_access);
        $this->assertEmpty($representation->can_access_submitted_by_group);
        $this->assertEmpty($representation->can_access_assigned_to_group);
        $this->assertEmpty($representation->can_admin);
        $this->assertCount(1, $representation->can_access_submitted_by_user);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access_submitted_by_user[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveSubmittedByGroup(): void
    {
        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS
                ],
                'permissions' => [
                    Tracker::PERMISSION_SUBMITTER => 1,
                ]
            ]
        ]);

        $representation = $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_access);
        $this->assertEmpty($representation->can_access_submitted_by_user);
        $this->assertEmpty($representation->can_access_assigned_to_group);
        $this->assertEmpty($representation->can_admin);
        $this->assertCount(1, $representation->can_access_submitted_by_group);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access_submitted_by_group[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveAssignedToGroup(): void
    {
        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS
                ],
                'permissions' => [
                    Tracker::PERMISSION_ASSIGNEE => 1,
                ]
            ]
        ]);

        $representation = $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_access);
        $this->assertEmpty($representation->can_access_submitted_by_user);
        $this->assertEmpty($representation->can_access_submitted_by_group);
        $this->assertEmpty($representation->can_admin);
        $this->assertCount(1, $representation->can_access_assigned_to_group);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access_assigned_to_group[0]->short_name);
    }

    public function testItReturnsAMixOfPermissions(): void
    {
        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $developers_id = 501;
        $developers_ugroup = new ProjectUGroup([
            'ugroup_id' => $developers_id,
            'name' => 'Developers',
            'group_id' => 202,
        ]);
        $tracker_admin_id = 502;
        $tracker_admin_ugroup = new ProjectUGroup([
            'ugroup_id' => $tracker_admin_id,
            'name' => 'TrackerAdmins',
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $developers_id)->andReturn($developers_ugroup);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $tracker_admin_id)->andReturn($tracker_admin_ugroup);

        $this->permissions_functions_wrapper->shouldReceive('getTrackerUGroupsPermissions')->with($this->tracker)->andReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::ANONYMOUS
                ],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1,
                ]
            ],
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS
                ],
                'permissions' => [
                    Tracker::PERMISSION_ADMIN => 1,
                ]
            ],
            [
                'ugroup' => [
                    'id' => $tracker_admin_id
                ],
                'permissions' => [
                    Tracker::PERMISSION_ADMIN => 1,
                ]
            ],
            [
                'ugroup' => [
                    'id' => $developers_id
                ],
                'permissions' => [
                    Tracker::PERMISSION_ASSIGNEE => 1,
                    Tracker::PERMISSION_SUBMITTER => 1,
                ]
            ],
        ]);

        $representation = $this->builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_access_submitted_by_user);

        $this->assertCount(1, $representation->can_access);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS], $representation->can_access[0]->short_name);

        $this->assertCount(1, $representation->can_access_assigned_to_group);
        $this->assertEquals('Developers', $representation->can_access_assigned_to_group[0]->short_name);

        $this->assertCount(1, $representation->can_access_submitted_by_group);
        $this->assertEquals('Developers', $representation->can_access_assigned_to_group[0]->short_name);

        $this->assertCount(2, $representation->can_admin);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_admin[0]->short_name);
        $this->assertEquals('TrackerAdmins', $representation->can_admin[1]->short_name);
    }
}
