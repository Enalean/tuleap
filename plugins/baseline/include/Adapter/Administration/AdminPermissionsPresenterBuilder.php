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
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final class AdminPermissionsPresenterBuilder implements IBuildAdminPermissionsPresenter
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
            Role::ADMIN
        );
        $readers        = $this->role_assignment_repository->findByProjectAndRole(
            ProjectProxy::buildFromProject($project),
            Role::READER
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
            $this->getAdministratorsPresenter($administrators_ugroup_id, $project),
            $this->getReadersPresenter($readers_ugroup_id, $administrators_ugroup_id, $project),
        );
    }

    /**
     * @param int[] $administrators_ugroup_id
     *
     * @return UgroupPresenter[]
     */
    private function getAdministratorsPresenter(array $administrators_ugroup_id, \Project $project): array
    {
        return array_map(
            static fn(\User_ForgeUGroup $ugroup) => new UgroupPresenter(
                $ugroup->getId(),
                $ugroup->getName(),
                in_array($ugroup->getId(), $administrators_ugroup_id, true)
            ),
            $this->user_group_factory->getProjectUGroupsWithMembersWithoutNobody($project)
        );
    }

    /**
     * @param int[] $administrators_ugroup_id
     *
     * @return UgroupPresenter[]
     */
    private function getReadersPresenter(
        array $readers_ugroup_id,
        array $administrators_ugroup_id,
        \Project $project,
    ): array {
        return array_values(
            array_filter(
                array_map(
                    static function (\User_ForgeUGroup $ugroup) use ($readers_ugroup_id, $administrators_ugroup_id) {
                        $is_ugroup_already_administrator = in_array($ugroup->getId(), $administrators_ugroup_id, true);
                        if ($is_ugroup_already_administrator) {
                            return null;
                        }

                        return new UgroupPresenter(
                            $ugroup->getId(),
                            $ugroup->getName(),
                            in_array($ugroup->getId(), $readers_ugroup_id, true)
                        );
                    },
                    $this->user_group_factory->getProjectUGroupsWithMembersWithoutNobody($project)
                )
            )
        );
    }
}
