<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\ProjectHistory;

use DateTimeImmutable;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\ProjectHistory\SaveTeamUpdateInProjectHistory;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

final class ProjectHistorySaver implements SaveTeamUpdateInProjectHistory
{
    public function __construct(
        private readonly \ProjectHistoryDao $project_history_dao,
        private RetrieveFullProject $retrieve_full_project,
        private RetrieveUser $retrieve_user,
    ) {
    }

    #[\Override]
    public function logTeamUpdate(ProgramForAdministrationIdentifier $program, UserReference $user_identifier, TeamProjectsCollection $teams, TeamCollection $new_teams): void
    {
        $team_projects = $teams->getTeamProjects();
        $project_ids   = array_map(
            static fn ($project) => $project->getId(),
            $team_projects
        );

        $project = $this->retrieve_full_project->getProject($program->id);
        $user    = $this->retrieve_user->getUserWithId($user_identifier);

        $this->project_history_dao->addHistory(
            $project,
            $user,
            new DateTimeImmutable(),
            ProgramHistoryEntry::UpdateTeamConfiguration->value,
            '',
            [
                $program->id,
                implode(', ', $project_ids),
                implode(', ', $new_teams->getTeamIds()),
            ]
        );
    }
}
