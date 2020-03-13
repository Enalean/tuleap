<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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


final class PermissionManagerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
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
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var array[]
     */
    private $permissions;

    protected function setUp(): void
    {
        $this->tracker_id  = 112;
        $project_id  = 34;
        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns($this->tracker_id);
        $this->tracker->shouldReceive('getGroupId')->andReturns($project_id);
        $this->permissions = array(
            ProjectUGroup::ANONYMOUS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::REGISTERED => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::PROJECT_ADMIN => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
        );
        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $this->permission_setter    = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager   = new Tracker_Permission_PermissionManager();
    }

    public function testItDoesNothingTryingToGrantAnonymousSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItGrantsRegisteredSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        $this->permissions_manager->shouldReceive('addPermission')->with(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::REGISTERED)->once();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItCannotGrantRegisterSubmittedOnlyWhenAnonymousHasFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        $this->permissions_manager->shouldReceive('addPermission')->with(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::ANONYMOUS)->once();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItRaisesAWarningWhenTryingToGrantRegisteredSubmittedOnlyWithAnonymousHasFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));
        $this->permissions[ProjectUGroup::ANONYMOUS]['permissions'] = array(
            Tracker::PERMISSION_FULL => 1
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, \Mockery::any())->once();

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }

    public function testItGrantsProjectMembersSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        $this->permissions_manager->shouldReceive('addPermission')->with(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItRevokesPreviousPermissionWhenGrantsProjectMembersSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = array(
            Tracker::PERMISSION_FULL => 1
        );

        $this->permissions_manager->shouldReceive('addPermission')->with(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->with(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }
}
