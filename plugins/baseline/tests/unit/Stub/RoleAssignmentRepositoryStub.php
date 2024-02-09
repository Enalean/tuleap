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

namespace Tuleap\Baseline\Stub;

use Tuleap\Baseline\Domain\BaselineUserGroup;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleAssignmentsUpdate;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;

final class RoleAssignmentRepositoryStub implements RoleAssignmentRepository
{
    private RoleAssignmentsUpdate $last_assignment_update;

    private function __construct(
        private array $baseline_administrators,
        private array $baseline_readers,
    ) {
    }

    public static function buildDefault(): self
    {
        return new self([], []);
    }

    /**
     * @param RoleAssignment[] $baseline_administrators
     * @param RoleAssignment[] $baseline_readers
     */
    public static function withRoles(array $baseline_administrators, array $baseline_readers): self
    {
        return new self(
            $baseline_administrators,
            $baseline_readers
        );
    }

    public function findByProjectAndRole(ProjectIdentifier $project, Role $role): array
    {
        return match ($role->getName()) {
            RoleBaselineAdmin::NAME => $this->baseline_administrators,
            RoleBaselineReader::NAME => $this->baseline_readers,
            default => []
        };
    }

    public function saveAssignmentsForProject(RoleAssignmentsUpdate $role_assignments_update): void
    {
        $this->last_assignment_update = $role_assignments_update;
    }

    public function getLastAssignmentUpdate(): ?RoleAssignmentsUpdate
    {
        return $this->last_assignment_update;
    }

    public function deleteUgroupAssignments(ProjectIdentifier $project, BaselineUserGroup $baseline_user_group): int
    {
        return 0;
    }
}
