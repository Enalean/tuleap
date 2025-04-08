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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\CoversClass(Tracker_Permission_PermissionManager::class)]
final class PermissionManagerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    private Tracker_Permission_PermissionSetter $permission_setter;
    private Tracker_Permission_PermissionManager $permission_manager;
    private PermissionsManager&MockObject $permissions_manager;
    private Tracker $tracker;
    private int $tracker_id = 112;
    private array $permissions;

    protected function setUp(): void
    {
        $project           = ProjectTestBuilder::aProject()->withId(34)->build();
        $this->tracker     = TrackerTestBuilder::aTracker()
            ->withId($this->tracker_id)
            ->withProject($project)
            ->build();
        $this->permissions = [
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
        $this->permissions_manager->method('addHistory');

        $this->permission_setter  = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager = new Tracker_Permission_PermissionManager();
    }

    public function testItDoesNothingTryingToGrantAnonymousSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ]);

        $this->permissions_manager->expects($this->never())->method('addPermission');
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItGrantsRegisteredSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ]);

        $this->permissions_manager->expects($this->once())->method('addPermission')->with(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::REGISTERED);
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItGrantsRegisteredAllAccess(): void
    {
        $this->permissions[ProjectUGroup::REGISTERED]['permissions'] = [
            Tracker::PERMISSION_SUBMITTER => 1,
        ];

        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_FULL,
        ]);

        $this->permissions_manager->expects($this->once())->method('addPermission')->with(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::REGISTERED);
        $this->permissions_manager->expects($this->once())->method('revokePermissionForUGroup')->with(Tracker::PERMISSION_SUBMITTER, $this->tracker_id, ProjectUGroup::REGISTERED);

        $this->permission_manager->save(
            $request,
            new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager)
        );
    }

    public function testItCannotGrantRegisterSubmittedOnlyWhenAnonymousHasFullAccess(): void
    {
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ]);

        $this->permissions_manager->expects($this->once())->method('addPermission')->with(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::ANONYMOUS);
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItRaisesAWarningWhenTryingToGrantRegisteredSubmittedOnlyWithAnonymousHasFullAccess(): void
    {
        $request                                                    = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ]);
        $this->permissions[ProjectUGroup::ANONYMOUS]['permissions'] = [
            Tracker::PERMISSION_FULL => 1,
        ];

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::WARN);

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }

    public function testItGrantsProjectMembersSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ]);

        $this->permissions_manager->expects($this->once())->method('addPermission')->with(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS);
        $this->permissions_manager->expects($this->never())->method('revokePermissionForUGroup');

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function testItRevokesPreviousPermissionWhenGrantsProjectMembersSubmittedOnly(): void
    {
        $request = new Tracker_Permission_PermissionRequest([
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ]);

        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = [
            Tracker::PERMISSION_FULL => 1,
        ];

        $this->permissions_manager->expects($this->once())->method('addPermission')->with(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS);
        $this->permissions_manager->expects($this->once())->method('revokePermissionForUGroup')->with(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS);

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }
}
