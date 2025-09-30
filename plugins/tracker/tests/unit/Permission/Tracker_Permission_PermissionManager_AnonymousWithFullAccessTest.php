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
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributor;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Permission_PermissionManager_AnonymousWithFullAccessTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\GlobalResponseMock;

    private Tracker_Permission_PermissionManager $permission_manager;
    private array $permissions;
    private Tracker $tracker;
    private int $tracker_id = 112;
    private PermissionsManager&MockObject $permissions_manager;

    private Tracker_Permission_PermissionSetter $permission_setter;

    #[\Override]
    protected function setUp(): void
    {
        $project       = ProjectTestBuilder::aProject()->withId(34)->build();
        $this->tracker = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->withProject($project)->build();

        TrackerSemanticContributor::setInstance(
            new TrackerSemanticContributor($this->tracker, null),
            $this->tracker,
        );

        $this->permissions_manager = $this->createMock(\PermissionsManager::class);
        $this->permission_manager  = new Tracker_Permission_PermissionManager();

        $permissions = [
            ProjectUGroup::ANONYMOUS       => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [
                    Tracker::PERMISSION_FULL => 1,
                ],
            ],
            ProjectUGroup::REGISTERED      => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                'ugroup'      => ['name' => 'whatever'],
                'permissions' => [],
            ],
        ];

        $this->permission_setter = new Tracker_Permission_PermissionSetter(
            $this->tracker,
            $permissions,
            $this->permissions_manager
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        TrackerSemanticContributor::clearInstances();
    }

    public function testItWarnsWhenAnonymousHaveFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest(
            [
                ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
                ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_FULL,
            ]
        );

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::WARN);

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

        $GLOBALS['Response']->expects($this->exactly(2))->method('addFeedback')->with(Feedback::WARN);

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

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
                Tracker::PERMISSION_FULL => 1,
            ],
        ];

        $this->permissions[ProjectUGroup::PROJECT_MEMBERS] = [
            'ugroup'      => ['name' => 'whatever'],
            'permissions' => [
                Tracker::PERMISSION_SUBMITTER_ONLY => 1,
            ],
        ];

        $this->permissions_manager->method('addHistory');
        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->once())->method('revokePermissionForUGroup')->with(
            Tracker::PERMISSION_SUBMITTER_ONLY,
            $this->tracker_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

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
            Tracker::PERMISSION_FULL => 1,
        ];
        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = [
            Tracker::PERMISSION_ADMIN => 1,
        ];

        $this->permissions_manager->method('addHistory');
        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->once())->method('revokePermissionForUGroup')->with(
            Tracker::PERMISSION_ADMIN,
            $this->tracker_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $permission_setter = new Tracker_Permission_PermissionSetter(
            $this->tracker,
            $this->permissions,
            $this->permissions_manager
        );
        $this->permission_manager->save($request, $permission_setter);
    }
}
