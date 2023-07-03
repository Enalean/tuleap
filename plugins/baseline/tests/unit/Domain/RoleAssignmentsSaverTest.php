<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Baseline\Domain;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Stub\AddRoleAssignmentsHistoryEntryStub;
use Tuleap\Baseline\Stub\RetrieveBaselineUserGroupStub;
use Tuleap\Baseline\Stub\RoleAssignmentRepositoryStub;
use Tuleap\Baseline\Stub\RoleAssignmentsToUpdateStub;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

class RoleAssignmentsSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItSavesRoleAssignments(): void
    {
        $role_assignment_repository = RoleAssignmentRepositoryStub::buildDefault();
        $history_entry_adder        = AddRoleAssignmentsHistoryEntryStub::build();
        $assignment_saver           = new RoleAssignmentsSaver(
            $role_assignment_repository,
            RetrieveBaselineUserGroupStub::withUserGroups(
                ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                ProjectUGroupTestBuilder::aCustomUserGroup(103)->build(),
                ProjectUGroupTestBuilder::aCustomUserGroup(104)->build(),
            ),
            new RoleAssignmentsHistorySaver(
                $history_entry_adder
            )
        );

        $project_identifier = ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->build());

        $assignment_saver->saveRoleAssignments(
            $project_identifier,
            RoleAssignmentsToUpdateStub::withUserGroupsIds(
                [102, 103],
                [103, 104]
            )
        );

        $role_assignments_update = $role_assignment_repository->getLastAssignmentUpdate();

        $readers_assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())
            ->withUserGroups(
                ProjectUGroupTestBuilder::aCustomUserGroup(103)->build(),
                ProjectUGroupTestBuilder::aCustomUserGroup(104)->build(),
            )
            ->build();

        $administrator_assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())
            ->withUserGroups(
                ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                ProjectUGroupTestBuilder::aCustomUserGroup(103)->build(),
            )
            ->build();

        self::assertNotNull($role_assignments_update);
        self::assertEquals($project_identifier, $role_assignments_update->getProject());
        self::assertEquals([...$administrator_assignments, ...$readers_assignments], $role_assignments_update->getAssignments());

        $save_history_parameters = $history_entry_adder->getAddedHistoryEntries();

        self::assertCount(2, $save_history_parameters);
        self::assertEquals(
            [
                $project_identifier,
                'perm_granted_for_baseline_readers',
                $readers_assignments,
            ],
            $save_history_parameters[0]
        );

        self::assertEquals(
            [
                $project_identifier,
                'perm_granted_for_baseline_administrators',
                $administrator_assignments,
            ],
            $save_history_parameters[1]
        );
    }

    public function testItThrowsWhenAUserGroupDoesNotBelongToCurrentProject(): void
    {
        $assignment_saver = new RoleAssignmentsSaver(
            RoleAssignmentRepositoryStub::buildDefault(),
            RetrieveBaselineUserGroupStub::withUserGroups(
                ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
            ),
            new RoleAssignmentsHistorySaver(
                AddRoleAssignmentsHistoryEntryStub::build()
            )
        );

        $this->expectException(UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException::class);

        $assignment_saver->saveRoleAssignments(
            ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->build()),
            RoleAssignmentsToUpdateStub::withUserGroupsIds(
                [105],
                []
            )
        );
    }
}
