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

declare(strict_types = 1);

final class Tracker_Permission_PermissionManager_RegisteredWithFullAccessTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalResponseMock, \Tuleap\GlobalLanguageMock;
    /**
     * @var Tracker_Permission_PermissionSetter
     */
    private $permission_setter;
    /**
     * @var Tracker_Permission_PermissionManager
     */
    private $permission_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PermissionsManager
     */
    private $permissions_manager;
    private $some_ugroupid = 369;

    protected function setUp(): void
    {
        $permissions = [
            ProjectUGroup::ANONYMOUS       => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => []
            ],
            ProjectUGroup::REGISTERED      => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1
                ]
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => []
            ],
            ProjectUGroup::PROJECT_ADMIN   => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => []
            ],
            $this->some_ugroupid           => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => []
            ],
        ];

        $tracker_id = 112;
        $project_id       = 34;
        $tracker    = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns($tracker_id);
        $tracker->shouldReceive('getGroupId')->andReturns($project_id);
        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $this->permission_manager  = new Tracker_Permission_PermissionManager();
        $this->permission_setter   = new Tracker_Permission_PermissionSetter(
            $tracker,
            $permissions,
            $this->permissions_manager
        );
    }

    public function testItWarnsWhenRegisteredHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItWarnsTwiceWhenRegisteredHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
                $this->some_ugroupid           => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->times(2);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->ordered();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->ordered();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantFullAccessToProjectMembersWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantSubmitterToProjectMembersWhenRegisteredHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantAssigneeToProjectMembersWhenRegisteredHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantAssigneeAndSubmitterToProjectMembersWhenRegisteredHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantSubmitterOnlyToProjectMembersWhenRegisteredHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }
}
