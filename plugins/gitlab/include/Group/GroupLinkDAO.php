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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

final class GroupLinkDAO extends DataAccessObject implements AddNewGroupLink, VerifyGroupIsAlreadyLinked, VerifyProjectIsAlreadyLinked, RetrieveGroupLinkById, UpdateBranchPrefixOfGroupLink, UpdateArtifactClosureOfGroupLink, RetrieveGroupLinkedToProject, DeleteGroupLink, UpdateSynchronizationDate
{
    #[\Override]
    public function addNewGroup(NewGroupLink $gitlab_group): int
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
                'create_branch_prefix'      => $gitlab_group->prefix_branch_name !== '' ? $gitlab_group->prefix_branch_name : null,
            ]
        );
    }

    #[\Override]
    public function isGroupAlreadyLinked(int $gitlab_group_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_gitlab_group WHERE gitlab_group_id = ?';
        $rows = $this->getDB()->run($sql, $gitlab_group_id);
        return count($rows) > 0;
    }

    #[\Override]
    public function isProjectAlreadyLinked(int $project_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_gitlab_group WHERE project_id = ?';
        $rows = $this->getDB()->run($sql, $project_id);
        return count($rows) > 0;
    }

    #[\Override]
    public function retrieveGroupLink(int $group_link_id): ?GroupLink
    {
        $row = $this->getDB()->row(
            'SELECT * FROM plugin_gitlab_group WHERE id = ?',
            $group_link_id
        );
        if ($row === null) {
            return null;
        }
        return GroupLink::buildGroupLinkFromRow($row);
    }

    #[\Override]
    public function retrieveGroupLinkedToProject(\Project $project): ?GroupLink
    {
        $row = $this->getDB()->row(
            'SELECT * FROM plugin_gitlab_group WHERE project_id = ?',
            (int) $project->getID()
        );
        if ($row === null) {
            return null;
        }
        return GroupLink::buildGroupLinkFromRow($row);
    }

    #[\Override]
    public function updateBranchPrefixOfGroupLink(
        int $id,
        string $prefix_branch_name,
    ): void {
        $this->getDB()->update(
            'plugin_gitlab_group',
            ['create_branch_prefix' => $prefix_branch_name !== '' ? $prefix_branch_name : null],
            ['id' => $id]
        );
    }

    #[\Override]
    public function updateArtifactClosureOfGroupLink(int $id, bool $allow_artifact_closure): void
    {
        $this->getDB()->update(
            'plugin_gitlab_group',
            ['allow_artifact_closure' => $allow_artifact_closure],
            ['id' => $id]
        );
    }

    #[\Override]
    public function deleteGroupLink(GroupLink $group_link): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($group_link) {
                $db->delete('plugin_gitlab_group_token', ['group_id' => $group_link->id]);
                $db->delete('plugin_gitlab_group_repository_integration', ['group_id' => $group_link->id]);
                $db->delete('plugin_gitlab_group', ['id' => $group_link->id]);
            }
        );
    }

    #[\Override]
    public function updateSynchronizationDate(GroupLink $group_link, \DateTimeImmutable $new_date): void
    {
        $this->getDB()->update(
            'plugin_gitlab_group',
            ['last_synchronization_date' => $new_date->getTimestamp()],
            ['id' => $group_link->id]
        );
    }
}
