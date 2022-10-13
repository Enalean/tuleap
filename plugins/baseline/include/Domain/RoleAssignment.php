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

namespace Tuleap\Baseline\Domain;

/**
 * Assign a pair of project / user group id to a role, which gives permissions.
 */
class RoleAssignment
{
    private function __construct(
        private ProjectIdentifier $project,
        private BaselineUserGroup $user_group,
        private Role $role,
    ) {
    }

    public function getProject(): ProjectIdentifier
    {
        return $this->project;
    }

    public function getUserGroupId(): int
    {
        return $this->user_group->getId();
    }

    public function getUserGroupName(): string
    {
        return $this->user_group->getName();
    }

    public function getRoleName(): string
    {
        return $this->role->getName();
    }

    /**
     * @param int[] $ugroups_ids
     * @throws UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException
     * @return self[]
     */
    public static function fromRoleAssignmentsIds(
        RetrieveBaselineUserGroup $retrieve_ugroups,
        ProjectIdentifier $project,
        Role $role,
        int ...$ugroups_ids,
    ): array {
        return array_map(
            static fn(int $ugroup_id) => new self(
                $project,
                $retrieve_ugroups->retrieveUserGroupFromBaselineProjectAndId($project, $ugroup_id),
                $role
            ),
            $ugroups_ids
        );
    }
}
