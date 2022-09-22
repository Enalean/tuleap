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

final class GitlabGroupFactory
{
    public function __construct(
        private VerifyGroupIsAlreadyLinked $group_integrated_verifier,
        private VerifyProjectIsAlreadyLinked $project_linked_verifier,
        private AddNewGroup $group_adder,
    ) {
    }

    /**
     * @throws GitlabGroupAlreadyLinkedToProjectException
     * @throws ProjectAlreadyLinkedToGitlabGroupException
     */
    public function createGroup(NewGroup $gitlab_group): GroupLink
    {
        if ($this->group_integrated_verifier->isGroupAlreadyLinked($gitlab_group->gitlab_group_id)) {
            throw new GitlabGroupAlreadyLinkedToProjectException($gitlab_group->gitlab_group_id);
        }

        if ($this->project_linked_verifier->isProjectAlreadyLinked($gitlab_group->project_id)) {
            throw new ProjectAlreadyLinkedToGitlabGroupException($gitlab_group->project_id);
        }

        $group_id = $this->group_adder->addNewGroup($gitlab_group);
        return GroupLink::buildGroupLinkFromInsertionRows($group_id, $gitlab_group);
    }
}
