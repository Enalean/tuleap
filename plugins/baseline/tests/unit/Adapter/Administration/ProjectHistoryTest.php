<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class ProjectHistoryTest extends TestCase
{
    private const PROJECT_ID          = 102;
    private const DEVELOPER_UGROUP_ID = 104;

    private \ProjectHistoryDao|\PHPUnit\Framework\MockObject\MockObject $dao;
    private ProjectHistory $history;
    private \Project $project;
    private ProjectProxy $project_proxy;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->build();

        $this->project_proxy = ProjectProxy::buildFromProject($this->project);

        $this->dao = $this->createMock(\ProjectHistoryDao::class);

        $this->history = new ProjectHistory($this->dao);
    }

    public function testSaveHistoryWithoutReadersNorAdministrators(): void
    {
        $this->dao
            ->expects(self::atLeast(2))
            ->method('groupAddHistory')
            ->withConsecutive(
                ['perm_reset_for_baseline_readers', '', self::PROJECT_ID],
                ['perm_reset_for_baseline_administrators', '', self::PROJECT_ID]
            );

        $this->history->saveHistory($this->project);
    }

    public function testSaveHistoryWithoutReaders(): void
    {
        $this->dao
            ->expects(self::atLeast(2))
            ->method('groupAddHistory')
            ->withConsecutive(
                ['perm_reset_for_baseline_readers', '', self::PROJECT_ID],
                [
                    'perm_granted_for_baseline_administrators',
                    'ugroup_project_members_name_key,Developers',
                    self::PROJECT_ID,
                ],
            );

        $this->history->saveHistory(
            $this->project,
            ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(
                ProjectUGroupTestBuilder::buildProjectMembers(),
                $this->getDeveloperUserGroup()
            )->withProject($this->project_proxy)->build(),
        );
    }

    public function testSaveHistoryWithoutAdministrators(): void
    {
        $this->dao
            ->expects(self::atLeast(2))
            ->method('groupAddHistory')
            ->withConsecutive(
                [
                    'perm_granted_for_baseline_readers',
                    'ugroup_project_members_name_key,Developers',
                    self::PROJECT_ID,
                ],
                ['perm_reset_for_baseline_administrators', '', self::PROJECT_ID],
            );

        $this->history->saveHistory(
            $this->project,
            ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())->withUserGroups(
                ProjectUGroupTestBuilder::buildProjectMembers(),
                $this->getDeveloperUserGroup()
            )->withProject($this->project_proxy)->build(),
        );
    }

    public function testSaveHistory(): void
    {
        $this->dao
            ->expects(self::atLeast(2))
            ->method('groupAddHistory')
            ->withConsecutive(
                [
                    'perm_granted_for_baseline_readers',
                    'Developers',
                    self::PROJECT_ID,
                ],
                [
                    'perm_granted_for_baseline_administrators',
                    'ugroup_project_members_name_key',
                    self::PROJECT_ID,
                ],
            );

        $this->history->saveHistory(
            $this->project,
            ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())->withProject($this->project_proxy)->build(),
            ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())->withUserGroups($this->getDeveloperUserGroup())->withProject($this->project_proxy)->build()
        );
    }

    private function getDeveloperUserGroup(): \ProjectUGroup
    {
        return ProjectUGroupTestBuilder::aCustomUserGroup(self::DEVELOPER_UGROUP_ID)->withName("Developers")->build();
    }
}
