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

final class RoleAssignmentsSaver
{
    public function __construct(
        private RoleAssignmentRepository $role_assignment_repository,
        private RetrieveBaselineUserGroup $user_group_retriever,
        private RoleAssignmentsHistorySaver $role_assignments_history_saver,
    ) {
    }

    /**
     * @throws UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException
     */
    public function saveRoleAssignments(ProjectIdentifier $project_identifier, RoleAssignmentsToUpdate $assignments_to_update): void
    {
        $assignments = array_merge(
            $this->getAssignmentsForRole($project_identifier, new RoleBaselineAdmin(), $assignments_to_update->getBaselineAdministratorsUserGroupsIds()),
            $this->getAssignmentsForRole($project_identifier, new RoleBaselineReader(), $assignments_to_update->getBaselineReadersUserGroupsIds()),
        );

        $this->role_assignment_repository->saveAssignmentsForProject(
            $this->buildRoleAssignmentUpdate($project_identifier, $assignments)
        );
        $this->role_assignments_history_saver->saveHistory(
            $this->buildRoleAssignmentUpdate($project_identifier, $assignments)
        );
    }

    /**
     * @param int[] $role_assignments_ids
     * @return RoleAssignment[]
     * @throws UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException
     */
    private function getAssignmentsForRole(ProjectIdentifier $project, Role $role, array $role_assignments_ids): array
    {
        return RoleAssignment::fromRoleAssignmentsIds(
            $this->user_group_retriever,
            $project,
            $role,
            ...$role_assignments_ids
        );
    }

    /**
     * @throws UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException
     */
    private function buildRoleAssignmentUpdate(ProjectIdentifier $project, array $role_assignments): RoleAssignmentsUpdate
    {
        return RoleAssignmentsUpdate::build(
            $project,
            ...$role_assignments
        );
    }
}
