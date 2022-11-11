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
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final class AdminPermissionsPresenterBuilder
{
    public function __construct(
        private \User_ForgeUserGroupFactory $user_group_factory,
        private RoleAssignmentRepository $role_assignment_repository,
    ) {
    }

    public function getPresenter(
        \Project $project,
        string $post_url,
        CSRFSynchronizerTokenInterface $token,
    ): AdminPermissionsPresenter {
        $administrators = $this->role_assignment_repository->findByProjectAndRole(
            ProjectProxy::buildFromProject($project),
            new RoleBaselineAdmin()
        );
        $readers        = $this->role_assignment_repository->findByProjectAndRole(
            ProjectProxy::buildFromProject($project),
            new RoleBaselineReader()
        );

        $administrators_ugroup_id = array_map(
            static fn(RoleAssignment $assignment) => $assignment->getUserGroupId(),
            $administrators
        );
        $readers_ugroup_id        = array_map(
            static fn(RoleAssignment $assignment) => $assignment->getUserGroupId(),
            $readers
        );

        return new AdminPermissionsPresenter(
            $post_url,
            $token,
            $this->getUserGroupsPresenter($administrators_ugroup_id, $project),
            $this->getUserGroupsPresenter($readers_ugroup_id, $project),
        );
    }

    /**
     * @param int[] $ugroup_ids
     *
     * @return UgroupPresenter[]
     */
    private function getUserGroupsPresenter(array $ugroup_ids, \Project $project): array
    {
        return array_map(
            static fn(\User_ForgeUGroup $ugroup) => new UgroupPresenter(
                $ugroup->getId(),
                $ugroup->getName(),
                in_array($ugroup->getId(), $ugroup_ids, true)
            ),
            $this->user_group_factory->getProjectUGroupsWithMembersWithoutNobody($project)
        );
    }
}
