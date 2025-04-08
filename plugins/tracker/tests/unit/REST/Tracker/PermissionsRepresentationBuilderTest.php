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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tracker;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\PermissionsFunctionsWrapper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID = 202;

    private UGroupRetriever $ugroup_manager;
    private PermissionsFunctionsWrapper&MockObject $permissions_functions_wrapper;
    private \PFUser $tracker_admin_user;
    private Project $project;
    private Tracker&MockObject $tracker;

    protected function setUp(): void
    {
        $this->tracker_admin_user = UserTestBuilder::buildWithDefaults();

        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getID')->willReturn(12);
        $this->tracker->method('getProject')->willReturn($this->project);
        $this->tracker->method('userIsAdmin')->willReturnCallback(fn (PFUser $user) => match ($user) {
            $this->tracker_admin_user => true,
            default => false,
        });

        $project_members_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => self::PROJECT_ID,
        ]);
        $this->ugroup_manager   = UGroupRetrieverStub::buildWithUserGroups($project_members_ugroup);

        $this->permissions_functions_wrapper = $this->createMock(PermissionsFunctionsWrapper::class);
    }

    public function testItReturnsNullWhenUserIsNotAdmin(): void
    {
        $a_random_user = UserTestBuilder::aRandomActiveUser()->build();

        $builder = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        $this->assertNull($builder->getPermissionsRepresentation($this->tracker, $a_random_user));
    }

    public function testItReturnsAnEmptyRepresentationWhenThereAreNoPermissions(): void
    {
        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([]);

        $builder = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        self::assertEquals(new PermissionsRepresentation([], [], [], [], []), $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user));
    }

    public function testItReturnsAGroupThatHaveAccess(): void
    {
        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1,
                ],
            ],
        ]);

        $builder        = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        $representation = $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        self::assertEmpty($representation->can_admin);
        self::assertEmpty($representation->can_access_submitted_by_group);
        self::assertEmpty($representation->can_access_assigned_to_group);
        self::assertEmpty($representation->can_access_submitted_by_user);
        self::assertCount(1, $representation->can_access);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveAdminAccess(): void
    {
        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_ADMIN => 1,
                ],
            ],
        ]);

        $builder        = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        $representation = $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        self::assertEmpty($representation->can_access);
        self::assertEmpty($representation->can_access_submitted_by_group);
        self::assertEmpty($representation->can_access_assigned_to_group);
        self::assertEmpty($representation->can_access_submitted_by_user);
        self::assertCount(1, $representation->can_admin);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_admin[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveSubmittedByThemAccess(): void
    {
        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_SUBMITTER_ONLY => 1,
                ],
            ],
        ]);

        $builder        = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        $representation = $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        self::assertEmpty($representation->can_access);
        self::assertEmpty($representation->can_access_submitted_by_group);
        self::assertEmpty($representation->can_access_assigned_to_group);
        self::assertEmpty($representation->can_admin);
        self::assertCount(1, $representation->can_access_submitted_by_user);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access_submitted_by_user[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveSubmittedByGroup(): void
    {
        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_SUBMITTER => 1,
                ],
            ],
        ]);

        $builder        = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        $representation = $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        self::assertEmpty($representation->can_access);
        self::assertEmpty($representation->can_access_submitted_by_user);
        self::assertEmpty($representation->can_access_assigned_to_group);
        self::assertEmpty($representation->can_admin);
        self::assertCount(1, $representation->can_access_submitted_by_group);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access_submitted_by_group[0]->short_name);
    }

    public function testItReturnsAGroupThatHaveAssignedToGroup(): void
    {
        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_ASSIGNEE => 1,
                ],
            ],
        ]);

        $builder        = new PermissionsRepresentationBuilder($this->ugroup_manager, $this->permissions_functions_wrapper);
        $representation = $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        self::assertEmpty($representation->can_access);
        self::assertEmpty($representation->can_access_submitted_by_user);
        self::assertEmpty($representation->can_access_submitted_by_group);
        self::assertEmpty($representation->can_admin);
        self::assertCount(1, $representation->can_access_assigned_to_group);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_access_assigned_to_group[0]->short_name);
    }

    public function testItReturnsAMixOfPermissions(): void
    {
        $project_members_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => self::PROJECT_ID,
        ]);
        $anonymous_ugroup       = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => self::PROJECT_ID,
        ]);
        $developers_id          = 501;
        $developers_ugroup      = new ProjectUGroup([
            'ugroup_id' => $developers_id,
            'name' => 'Developers',
            'group_id' => self::PROJECT_ID,
        ]);
        $tracker_admin_id       = 502;
        $tracker_admin_ugroup   = new ProjectUGroup([
            'ugroup_id' => $tracker_admin_id,
            'name' => 'TrackerAdmins',
            'group_id' => self::PROJECT_ID,
        ]);

        $ugroup_manager = UGroupRetrieverStub::buildWithUserGroups(
            $project_members_ugroup,
            $anonymous_ugroup,
            $developers_ugroup,
            $tracker_admin_ugroup,
        );

        $this->permissions_functions_wrapper->method('getTrackerUGroupsPermissions')->with($this->tracker)->willReturn([
            [
                'ugroup' => [
                    'id' => ProjectUGroup::ANONYMOUS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1,
                ],
            ],
            [
                'ugroup' => [
                    'id' => ProjectUGroup::PROJECT_MEMBERS,
                ],
                'permissions' => [
                    Tracker::PERMISSION_ADMIN => 1,
                ],
            ],
            [
                'ugroup' => [
                    'id' => $tracker_admin_id,
                ],
                'permissions' => [
                    Tracker::PERMISSION_ADMIN => 1,
                ],
            ],
            [
                'ugroup' => [
                    'id' => $developers_id,
                ],
                'permissions' => [
                    Tracker::PERMISSION_ASSIGNEE => 1,
                    Tracker::PERMISSION_SUBMITTER => 1,
                ],
            ],
        ]);

        $builder        = new PermissionsRepresentationBuilder($ugroup_manager, $this->permissions_functions_wrapper);
        $representation = $builder->getPermissionsRepresentation($this->tracker, $this->tracker_admin_user);
        self::assertEmpty($representation->can_access_submitted_by_user);

        self::assertCount(1, $representation->can_access);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS], $representation->can_access[0]->short_name);

        self::assertCount(1, $representation->can_access_assigned_to_group);
        self::assertEquals('Developers', $representation->can_access_assigned_to_group[0]->short_name);

        self::assertCount(1, $representation->can_access_submitted_by_group);
        self::assertEquals('Developers', $representation->can_access_assigned_to_group[0]->short_name);

        self::assertCount(2, $representation->can_admin);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_admin[0]->short_name);
        self::assertEquals('TrackerAdmins', $representation->can_admin[1]->short_name);
    }
}
