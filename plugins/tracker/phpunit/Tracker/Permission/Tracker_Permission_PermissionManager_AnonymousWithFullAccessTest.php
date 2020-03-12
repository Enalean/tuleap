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

final class Tracker_Permission_PermissionManager_AnonymousWithFullAccessTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock, \Tuleap\GlobalResponseMock;

    /**
     * @var Tracker_Permission_PermissionManager
     */
    private $permission_manager;
    /**
     * @var array[]
     */
    private $permissions;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var Tracker_Permission_PermissionSetter
     */
    private $permission_setter;

    protected function setUp(): void
    {
        $this->tracker_id = 112;
        $project_id       = 34;
        $this->tracker    = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns($this->tracker_id);
        $this->tracker->shouldReceive('getGroupId')->andReturns($project_id);

        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $this->permission_manager  = new Tracker_Permission_PermissionManager();

        $permissions = [
            ProjectUGroup::ANONYMOUS       => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1
                ]
            ],
            ProjectUGroup::REGISTERED      => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => []
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => []
            ],
        ];

        $this->permission_setter = new Tracker_Permission_PermissionSetter(
            $this->tracker,
            $permissions,
            $this->permissions_manager
        );
    }

    public function testItWarnsWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }


    public function testItWarnsTwiceWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_SUBMITTER,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
            ]
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->times(2);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->ordered();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->ordered();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantFullAccessToRegisteredWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantSubmitterOnlyToRegisteredWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantFullAccessToProjectMembersWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantSubmitterToProjectMembersWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantAssigneeToProjectMembersWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantAssigneeAndSubmitterToProjectMembersWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesntGrantSubmitterOnlyToProjectMembersWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
            ]
        );

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItRevokesPreExistingPermission(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
            ]
        );

        $this->permissions[ProjectUGroup::ANONYMOUS] = [
            'ugroup'      => ['name' => 'whatever'],
            'permissions' => [
                Tracker::PERMISSION_FULL => 1
            ]
        ];

        $this->permissions[ProjectUGroup::PROJECT_MEMBERS] = [
            'ugroup'      => ['name' => 'whatever'],
            'permissions' => [
                Tracker::PERMISSION_SUBMITTER_ONLY => 1
            ]
        ];

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->with(
            Tracker::PERMISSION_SUBMITTER_ONLY,
            $this->tracker_id,
            ProjectUGroup::PROJECT_MEMBERS
        )->once();

        $permission_setter = new Tracker_Permission_PermissionSetter(
            $this->tracker,
            $this->permissions,
            $this->permissions_manager
        );
        $this->permission_manager->save($request, $permission_setter);
    }

    public function testItRevokesAdminPermission(): void
    {
        $request                                                          = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_NONE,
            ]
        );
        $this->permissions[ProjectUGroup::ANONYMOUS]['permissions']       = [
            Tracker::PERMISSION_FULL => 1
        ];
        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = [
            Tracker::PERMISSION_ADMIN => 1
        ];

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->with(
            Tracker::PERMISSION_ADMIN,
            $this->tracker_id,
            ProjectUGroup::PROJECT_MEMBERS
        )->once();

        $permission_setter = new Tracker_Permission_PermissionSetter(
            $this->tracker,
            $this->permissions,
            $this->permissions_manager
        );
        $this->permission_manager->save($request, $permission_setter);
    }
}
