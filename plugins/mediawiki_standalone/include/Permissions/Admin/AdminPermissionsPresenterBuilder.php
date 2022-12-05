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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final class AdminPermissionsPresenterBuilder
{
    public function __construct(
        private ProjectPermissionsRetriever $permissions_retriever,
        private \User_ForgeUserGroupFactory $user_group_factory,
    ) {
    }

    public function getPresenter(
        \Project $project,
        string $post_url,
        CSRFSynchronizerTokenInterface $token,
    ): AdminPermissionsPresenter {
        $project_permissions = $this->permissions_retriever->getProjectPermissions($project);

        return new AdminPermissionsPresenter(
            $post_url,
            $token,
            $this->getUserGroupsPresenter(
                $project_permissions->readers,
                $this->user_group_factory->getAllForProjectWithoutNobody($project),
            ),
            $this->getUserGroupsPresenter(
                $project_permissions->writers,
                $this->user_group_factory->getAllForProjectWithoutNobodyNorAnonymous($project),
            ),
            $this->getUserGroupsPresenter(
                $project_permissions->admins,
                $this->user_group_factory->getProjectUGroupsWithMembersWithoutNobody($project),
            ),
        );
    }

    /**
     * @param int[] $ugroup_ids
     * @param \User_ForgeUGroup[] $allowed_ugroups
     *
     * @return UserGroupPresenter[]
     */
    private function getUserGroupsPresenter(array $ugroup_ids, array $allowed_ugroups): array
    {
        return array_map(
            static fn(\User_ForgeUGroup $ugroup) => new UserGroupPresenter(
                $ugroup->getId(),
                $ugroup->getName(),
                in_array($ugroup->getId(), $ugroup_ids, true)
            ),
            $allowed_ugroups
        );
    }
}
