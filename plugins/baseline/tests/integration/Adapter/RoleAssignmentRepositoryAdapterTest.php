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
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class RoleAssignmentRepositoryAdapterTest extends TestCase
{
    private RoleAssignmentRepositoryAdapter $repository;
    private ProjectIdentifier $project;

    protected function setUp(): void
    {
        $this->repository = new RoleAssignmentRepositoryAdapter($this->getDB());
        $this->project    = new class implements ProjectIdentifier {
            public function getID(): int
            {
                return 101;
            }
        };
    }

    public function tearDown(): void
    {
        $this->getDB()->run('DELETE FROM plugin_baseline_role_assignment');
    }

    private function getDB(): EasyDB
    {
        return DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testCanInsertRolesAndRetrieveThem(): void
    {
        $this->repository->saveAssignmentsForProject(
            $this->project,
            new RoleAssignment($this->project, 3, Role::ADMIN),
            new RoleAssignment($this->project, 101, Role::READER),
            new RoleAssignment($this->project, 102, Role::READER),
        );

        $administrators = $this->repository->findByProjectAndRole($this->project, Role::ADMIN);
        self::assertEquals(
            [3],
            $this->getUgroupIdsFrom($administrators)
        );

        $readers = $this->repository->findByProjectAndRole($this->project, Role::READER);
        self::assertEquals(
            [101, 102],
            $this->getUgroupIdsFrom($readers),
        );
    }

    public function testCanRemoveAllRoles(): void
    {
        // set up some roles…
        $this->repository->saveAssignmentsForProject(
            $this->project,
            new RoleAssignment($this->project, 3, Role::ADMIN),
            new RoleAssignment($this->project, 101, Role::READER),
            new RoleAssignment($this->project, 102, Role::READER),
        );

        // …and remove them
        $this->repository->saveAssignmentsForProject($this->project);

        $administrators = $this->repository->findByProjectAndRole($this->project, Role::ADMIN);
        self::assertEmpty($administrators);

        $readers = $this->repository->findByProjectAndRole($this->project, Role::READER);
        self::assertEmpty($readers);
    }

    /**
     * @param RoleAssignment[] $assigments
     *
     * @return int[]
     */
    private function getUgroupIdsFrom(array $assigments): array
    {
        return array_map(
            static fn (RoleAssignment $role) => $role->getUserGroupId(),
            $assigments,
        );
    }
}
