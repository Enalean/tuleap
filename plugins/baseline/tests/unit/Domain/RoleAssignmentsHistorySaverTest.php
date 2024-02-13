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

declare(strict_types=1);

namespace Tuleap\Baseline\Domain;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Adapter\UserGroupProxy;
use Tuleap\Baseline\Stub\AddRoleAssignmentsHistoryEntryStub;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class RoleAssignmentsHistorySaverTest extends TestCase
{
    private const PROJECT_ID          = 102;
    private const DEVELOPER_UGROUP_ID = 104;

    private ProjectProxy $project_proxy;

    protected function setUp(): void
    {
        $this->project_proxy = ProjectProxy::buildFromProject(
            ProjectTestBuilder::aProject()
                ->withId(self::PROJECT_ID)
                ->build()
        );
    }

    public function testSaveHistoryWithoutReadersNorAdministrators(): void
    {
        $add_role_assignments_history_entry = AddRoleAssignmentsHistoryEntryStub::build();

        $this->buildAssignmentHistorySaver(
            $add_role_assignments_history_entry
        )->saveHistory(
            RoleAssignmentsUpdate::build($this->project_proxy)
        );

        $added_entries = $add_role_assignments_history_entry->getAddedHistoryEntries();

        self::assertCount(2, $added_entries);
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_reset_for_baseline_readers',
                [],
            ],
            $added_entries[0]
        );
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_reset_for_baseline_administrators',
                [],
            ],
            $added_entries[1]
        );
    }

    public function testSaveHistoryWithoutReaders(): void
    {
        $add_role_assignments_history_entry = AddRoleAssignmentsHistoryEntryStub::build();
        $admin_role_assignments             = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembers(),
            $this->getDeveloperUserGroup()
        )->withProject($this->project_proxy)->build();

        $this->buildAssignmentHistorySaver(
            $add_role_assignments_history_entry
        )->saveHistory(RoleAssignmentsUpdate::build($this->project_proxy, ...$admin_role_assignments));

        $added_entries = $add_role_assignments_history_entry->getAddedHistoryEntries();

        self::assertCount(2, $added_entries);
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_reset_for_baseline_readers',
                [],
            ],
            $added_entries[0]
        );
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_granted_for_baseline_administrators',
                $admin_role_assignments,
            ],
            $added_entries[1]
        );
    }

    public function testSaveHistoryWithoutAdministrators(): void
    {
        $add_role_assignments_history_entry = AddRoleAssignmentsHistoryEntryStub::build();
        $readers_role_assignments           = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())->withUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembers(),
            $this->getDeveloperUserGroup()
        )->withProject($this->project_proxy)->build();

        $this->buildAssignmentHistorySaver(
            $add_role_assignments_history_entry
        )->saveHistory(RoleAssignmentsUpdate::build($this->project_proxy, ...$readers_role_assignments));

        $added_entries = $add_role_assignments_history_entry->getAddedHistoryEntries();

        self::assertCount(2, $added_entries);
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_granted_for_baseline_readers',
                $readers_role_assignments,
            ],
            $added_entries[0]
        );
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_reset_for_baseline_administrators',
                [],
            ],
            $added_entries[1]
        );
    }

    public function testSaveHistory(): void
    {
        $readers_role_assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())
            ->withUserGroups($this->getDeveloperUserGroup())
            ->withProject($this->project_proxy)
            ->build();

        $admin_role_assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())
            ->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())
            ->withProject($this->project_proxy)
            ->build();

        $add_role_assignments_history_entry = AddRoleAssignmentsHistoryEntryStub::build();
        $this->buildAssignmentHistorySaver(
            $add_role_assignments_history_entry
        )->saveHistory(RoleAssignmentsUpdate::build($this->project_proxy, ...$admin_role_assignments, ...$readers_role_assignments));

        $added_entries = $add_role_assignments_history_entry->getAddedHistoryEntries();

        self::assertCount(2, $added_entries);
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_granted_for_baseline_readers',
                $readers_role_assignments,
            ],
            $added_entries[0]
        );
        self::assertEquals(
            [
                $this->project_proxy,
                'perm_granted_for_baseline_administrators',
                $admin_role_assignments,
            ],
            $added_entries[1]
        );
    }

    public function testItSavesUgroupDeletionInHistory(): void
    {
        $ugroup_proxy = UserGroupProxy::fromProjectUGroup(
            ProjectUGroupTestBuilder::aCustomUserGroup(963)->build(),
        );

        $add_role_assignments_history_entry = AddRoleAssignmentsHistoryEntryStub::build();
        $this->buildAssignmentHistorySaver(
            $add_role_assignments_history_entry
        )->saveUgroupDeletionHistory(
            $this->project_proxy,
            $ugroup_proxy,
        );

        $added_entries = $add_role_assignments_history_entry->getAddedHistoryEntries();
        self::assertCount(1, $added_entries);
        self::assertEquals(
            [
                $this->project_proxy,
                'all_baseline_perms_removed_for_ugroup',
                $ugroup_proxy,
            ],
            $added_entries[0]
        );
    }

    private function buildAssignmentHistorySaver(AddRoleAssignmentsHistoryEntry $add_role_assignments_history_entry): RoleAssignmentsHistorySaver
    {
        return new RoleAssignmentsHistorySaver(
            $add_role_assignments_history_entry
        );
    }

    private function getDeveloperUserGroup(): \ProjectUGroup
    {
        return ProjectUGroupTestBuilder::aCustomUserGroup(self::DEVELOPER_UGROUP_ID)->withName("Developers")->build();
    }
}
