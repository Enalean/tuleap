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

use Tuleap\MediawikiStandalone\Permissions\ReadersRetriever;
use Tuleap\MediawikiStandalone\Permissions\WritersRetriever;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final class AdminPermissionsPresenterBuilder
{
    public function __construct(
        private ReadersRetriever $readers_retriever,
        private WritersRetriever $writers_retriever,
        private \User_ForgeUserGroupFactory $user_group_factory,
    ) {
    }

    public function getPresenter(
        \Project $project,
        string $post_url,
        CSRFSynchronizerTokenInterface $token,
    ): AdminPermissionsPresenter {
        return new AdminPermissionsPresenter(
            $post_url,
            $token,
            $this->getUserGroupsPresenter(
                $this->readers_retriever->getReadersUgroupIds($project),
                $this->user_group_factory->getAllForProjectWithoutNobody($project),
            ),
            $this->getUserGroupsPresenter(
                $this->writers_retriever->getWritersUgroupIds($project),
                $this->user_group_factory->getAllForProjectWithoutNobodyNorAnonymous($project),
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
