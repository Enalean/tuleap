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

final class GitlabGroupFactory implements BuildGitlabGroup
{
    public function __construct(private GitlabGroupDAO $gitlab_group_DAO)
    {
    }

    /**
     * @throws GitlabGroupAlreadyExistsException
     */
    public function createGroup(GitlabGroupDBInsertionRepresentation $gitlab_group): GitlabGroup
    {
        if ($this->gitlab_group_DAO->searchGroupByGitlabGroupId($gitlab_group->gitlab_group_id)) {
            throw new GitlabGroupAlreadyExistsException($gitlab_group->name);
        }

        $group_id = $this->gitlab_group_DAO->insertNewGitlabGroup($gitlab_group);
        return GitlabGroup::buildGitlabGroupFromInsertionRows($group_id, $gitlab_group);
    }
}
