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

declare(strict_types=1);

final class Tracker_Permission_PermissionManager_CheckRequestValidityTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

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
     * @var array[]
     */
    private $permissions;

    protected function setUp(): void
    {
        $tracker_id  = 112;
        $project_id  = 34;
        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns($tracker_id);
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

    public function testItDisplaysAFeedbackErrorIfAssignedToSemanticIsNotDefined(): void
    {
        $this->tracker->shouldReceive('getContributorField')->andReturns(null);
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ));

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::ERROR, \Mockery::any(), \Mockery::any())->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesNotDisplayAFeedbackErrorIfAssignedToSemanticIsDefined(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->tracker->shouldReceive('getContributorField')->andReturns($field);
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ));

        $this->permissions_manager->shouldReceive('addPermission')->once();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::INFO, \Mockery::any())->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesNotApplyPermissionsOnProjectAdmins(): void
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_ADMIN    => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        $this->permissions_manager->shouldReceive('addPermission')->never();
        $this->permissions_manager->shouldReceive('revokePermissionForUGroup')->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }
}
