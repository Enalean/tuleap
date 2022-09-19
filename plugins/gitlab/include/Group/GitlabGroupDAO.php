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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group;

use Tuleap\DB\DataAccessObject;

final class GitlabGroupDAO extends DataAccessObject implements AddNewGroup, VerifyGroupIsAlreadyLinked, VerifyProjectIsAlreadyLinked, RetrieveGroupLink, UpdateBranchPrefixOfGroup
{
    public function addNewGroup(NewGroup $gitlab_group): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlab_group',
            [
                'gitlab_group_id'           => $gitlab_group->gitlab_group_id,
                'project_id'                => $gitlab_group->project_id,
                'name'                      => $gitlab_group->name,
                'full_path'                 => $gitlab_group->full_path,
                'web_url'                   => $gitlab_group->web_url,
                'avatar_url'                => $gitlab_group->avatar_url,
                'last_synchronization_date' => $gitlab_group->last_synchronization_date->getTimestamp(),
                'allow_artifact_closure'    => $gitlab_group->allow_artifact_closure,
                'create_branch_prefix'      => $gitlab_group->prefix_branch_name,
            ]
        );
    }

    public function isGroupAlreadyLinked(int $gitlab_group_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_gitlab_group WHERE gitlab_group_id = ?';
        $rows = $this->getDB()->run($sql, $gitlab_group_id);
        return count($rows) > 0;
    }

    public function isProjectAlreadyLinked(int $project_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_gitlab_group WHERE project_id = ?';
        $rows = $this->getDB()->run($sql, $project_id);
        return count($rows) > 0;
    }

    /**
     * @psalm-return null|array{id: int, gitlab_group_id: int, project_id: int, name:string, full_path: string, web_url: string, avatar_url: string, last_synchronization_date: int, allow_artifact_closure: int, create_branch_prefix: string}
     */
    public function retrieveGroupLink(int $gitlab_group_link_int): ?array
    {
        return $this->getDB()->row(
            "SELECT * FROM plugin_gitlab_group WHERE id = ?",
            $gitlab_group_link_int
        );
    }

    public function updateBranchPrefixOfGroupLink(
        int $id,
        string $create_branch_prefix,
    ): void {
        $this->getDB()->update(
            'plugin_gitlab_group',
            ['create_branch_prefix' => $create_branch_prefix],
            ['id' => $id]
        );
    }
}
