<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\Baseline\Domain\RoleAssignmentsUpdate;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Baseline\Stub\RetrieveBaselineUserGroupStub;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class RoleAssignmentRepositoryAdapterTest extends TestIntegrationTestCase
{
    private RoleAssignmentRepositoryAdapter $repository;
    private ProjectIdentifier $project;

    protected function setUp(): void
    {
        $this->repository = new RoleAssignmentRepositoryAdapter(
            $this->getDB(),
            RetrieveBaselineUserGroupStub::withUserGroups(
                ProjectUGroupTestBuilder::buildProjectMembers(),
                ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                ProjectUGroupTestBuilder::aCustomUserGroup(103)->build()
            )
        );

        $this->project = new /** @psalm-immutable */ class implements ProjectIdentifier {
            public function getID(): int
            {
                return 101;
            }
        };
    }

    private function getDB(): EasyDB
    {
        return DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testCanInsertRolesAndRetrieveThem(): void
    {
        $this->repository->saveAssignmentsForProject(
            RoleAssignmentsUpdate::build(
                $this->project,
                ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())->withProject($this->project)->build(),
                ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())->withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(103)->build()
                )->withProject($this->project)->build(),
            )
        );

        $administrators = $this->repository->findByProjectAndRole($this->project, new RoleBaselineAdmin());
        self::assertEquals(
            [3],
            $this->getUgroupIdsFrom($administrators)
        );

        $readers = $this->repository->findByProjectAndRole($this->project, new RoleBaselineReader());
        self::assertEquals(
            [102, 103],
            $this->getUgroupIdsFrom($readers),
        );
    }

    public function testCanRemoveAllRoles(): void
    {
        // set up some roles…
        $this->repository->saveAssignmentsForProject(
            RoleAssignmentsUpdate::build(
                $this->project,
                ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())->withProject($this->project)->build(),
                ...RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineReader())->withUserGroups(
                    ProjectUGroupTestBuilder::aCustomUserGroup(102)->build(),
                    ProjectUGroupTestBuilder::aCustomUserGroup(103)->build()
                )->withProject($this->project)->build(),
            )
        );

        // …and remove them
        $this->repository->saveAssignmentsForProject(RoleAssignmentsUpdate::build($this->project));

        $administrators = $this->repository->findByProjectAndRole($this->project, new RoleBaselineAdmin());
        self::assertEmpty($administrators);

        $readers = $this->repository->findByProjectAndRole($this->project, new RoleBaselineReader());
        self::assertEmpty($readers);
    }

    /**
     * @param RoleAssignment[] $assignments
     *
     * @return int[]
     */
    private function getUgroupIdsFrom(array $assignments): array
    {
        return array_map(
            static fn (RoleAssignment $role) => $role->getUserGroupId(),
            $assignments,
        );
    }
}
