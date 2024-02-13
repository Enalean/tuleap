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

final class RoleAssignmentsDeleter
{
    public function __construct(
        private readonly RoleAssignmentRepository $role_assignment_repository,
        private readonly RoleAssignmentsHistorySaver $role_assignments_history_saver,
    ) {
    }

    public function deleteRoleAssignments(ProjectIdentifier $project_identifier, BaselineUserGroup $baseline_user_group): void
    {
        $delete_row_number = $this->role_assignment_repository->deleteUgroupAssignments(
            $project_identifier,
            $baseline_user_group,
        );

        if ($delete_row_number > 0) {
            $this->role_assignments_history_saver->saveUgroupDeletionHistory(
                $project_identifier,
                $baseline_user_group,
            );
        }
    }
}
