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

namespace Tuleap\Baseline\Adapter\Administration;

use ProjectHistoryDao;
use Tuleap\Baseline\Domain\AddRoleAssignmentsHistoryEntry;
use Tuleap\Baseline\Domain\BaselineUserGroup;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\User\ProvideCurrentUser;

final class RoleAssignmentsHistoryEntryAdder implements AddRoleAssignmentsHistoryEntry
{
    public function __construct(
        private readonly ProjectHistoryDao $dao,
        private readonly ProjectByIDFactory $project_manager,
        private readonly ProvideCurrentUser $user_manager,
    ) {
    }

    public function addProjectHistoryEntryForRoleAndGroups(
        ProjectIdentifier $project,
        string $history_key,
        RoleAssignment ...$assignments,
    ): void {
        $this->dao->groupAddHistory(
            $history_key,
            implode(',', array_map(static fn(RoleAssignment $assignment) => $assignment->getUserGroupName(), $assignments)),
            $project->getID()
        );
    }

    public function addProjectHistoryEntryForUgroupDeletion(
        ProjectIdentifier $project,
        string $history_key,
        BaselineUserGroup $baseline_user_group,
    ): void {
        $this->dao->addHistory(
            $this->project_manager->getProjectById($project->getID()),
            $this->user_manager->getCurrentUser(),
            new \DateTimeImmutable(),
            $history_key,
            $baseline_user_group->getName(),
        );
    }
}
