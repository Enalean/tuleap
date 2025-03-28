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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Permission_PermissionManager_RegisteredWithFullAccessTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    private Tracker_Permission_PermissionSetter $permission_setter;
    private Tracker_Permission_PermissionManager $permission_manager;

    private PermissionsManager&MockObject $permissions_manager;
    private int $some_ugroupid = 369;

    protected function setUp(): void
    {
        $permissions = [
            ProjectUGroup::ANONYMOUS       => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            ProjectUGroup::REGISTERED      => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1,
                ],
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            ProjectUGroup::PROJECT_ADMIN   => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            $this->some_ugroupid           => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
        ];

        $project = ProjectTestBuilder::aProject()->withId(34)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(112)->withProject($project)->build();

        Tracker_Semantic_Contributor::setInstance(
            new Tracker_Semantic_Contributor($tracker, null),
            $tracker,
        );

        $this->permissions_manager = $this->createMock(\PermissionsManager::class);
        $this->permission_manager  = new Tracker_Permission_PermissionManager();
        $this->permission_setter   = new Tracker_Permission_PermissionSetter(
            $tracker,
            $permissions,
            $this->permissions_manager
        );
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Contributor::clearInstances();
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

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::WARN);

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

        $GLOBALS['Response']->expects($this->exactly(2))->method('addFeedback')->with(Feedback::WARN);

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

        $this->permission_manager->save($request, $this->permission_setter);
    }
}
