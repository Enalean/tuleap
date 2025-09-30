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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Permission_PermissionManager_CheckRequestValidityTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    private Tracker_Permission_PermissionSetter $permission_setter;
    private Tracker_Permission_PermissionManager $permission_manager;
    private PermissionsManager&MockObject $permissions_manager;
    private Tracker&MockObject $tracker;
    private array $permissions;

    #[\Override]
    protected function setUp(): void
    {
        $tracker_id    = 112;
        $project_id    = 34;
        $this->tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->tracker->method('getId')->willReturn($tracker_id);
        $this->tracker->method('getGroupId')->willReturn($project_id);
        $this->permissions         = [
            ProjectUGroup::ANONYMOUS => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            ProjectUGroup::REGISTERED => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            ProjectUGroup::PROJECT_ADMIN => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
        ];
        $this->permissions_manager = $this->createMock(\PermissionsManager::class);
        $this->permission_setter   = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager  = new Tracker_Permission_PermissionManager();
    }

    public function testItDisplaysAFeedbackErrorIfAssignedToSemanticIsNotDefined(): void
    {
        $this->tracker->method('getContributorField')->willReturn(null);
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ]);

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::ERROR);

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesNotDisplayAFeedbackErrorIfAssignedToSemanticIsDefined(): void
    {
        $field = $this->createMock(TrackerField::class);
        $this->tracker->method('getContributorField')->willReturn($field);
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ]);

        $this->permissions_manager->expects($this->once())->method('addPermission');
        $this->permissions_manager->method('addHistory');
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::INFO);

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItDoesNotApplyPermissionsOnProjectAdmins(): void
    {
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_ADMIN    => Tracker_Permission_Command::PERMISSION_FULL,
        ]);

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

        $this->permission_manager->save($request, $this->permission_setter);
    }
}
