<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\Baseline\Domain\BaselineUserGroup;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\RetrieveBaselineUserGroup;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleAssignmentsUpdate;

class RoleAssignmentRepositoryAdapter implements RoleAssignmentRepository
{
    public function __construct(private EasyDB $db, private RetrieveBaselineUserGroup $ugroup_retriever)
    {
    }

    /**
     * @return RoleAssignment[]
     */
    public function findByProjectAndRole(ProjectIdentifier $project, Role $role): array
    {
        $user_groups_ids = $this->db->column(
            "SELECT user_group_id
                    FROM plugin_baseline_role_assignment
                    WHERE project_id = ?
                    AND role = ?",
            [$project->getID(), $role->getName()]
        );

        return RoleAssignment::fromRoleAssignmentsIds(
            $this->ugroup_retriever,
            $project,
            $role,
            ...$user_groups_ids
        );
    }

    public function saveAssignmentsForProject(RoleAssignmentsUpdate $role_assignments_update): void
    {
        $insertions = [];
        foreach ($role_assignments_update->getAssignments() as $assignment) {
            $insertions[] = [
                'user_group_id' => $assignment->getUserGroupId(),
                'role'          => $assignment->getRoleName(),
                'project_id'    => $assignment->getProject()->getID(),
            ];
        }

        $this->db->tryFlatTransaction(
            function () use ($role_assignments_update, $insertions): void {
                $this->db->delete('plugin_baseline_role_assignment', ['project_id' => $role_assignments_update->getProject()->getID()]);
                if (! empty($insertions)) {
                    $this->db->insertMany('plugin_baseline_role_assignment', $insertions);
                }
            }
        );
    }

    public function deleteUgroupAssignments(
        ProjectIdentifier $project,
        BaselineUserGroup $baseline_user_group,
    ): int {
        return $this->db->delete(
            'plugin_baseline_role_assignment',
            [
                'project_id' => $project->getID(),
                'user_group_id' => $baseline_user_group->getId(),
            ]
        );
    }
}
