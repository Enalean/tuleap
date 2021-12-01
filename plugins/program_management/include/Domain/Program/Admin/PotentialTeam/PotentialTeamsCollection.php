<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\AllProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Workspace\SearchProjectsUserIsAdmin;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class PotentialTeamsCollection
{
    /**
     * @var PotentialTeam[]
     */
    private array $potential_teams;

    private function __construct(array $potential_teams)
    {
        $this->potential_teams = $potential_teams;
    }

    public static function buildPotentialTeams(
        SearchTeamsOfProgram $teams_of_program_searcher,
        AllProgramSearcher $all_program_searcher,
        SearchProjectsUserIsAdmin $retrieve_project_user_is_admin,
        ProgramForAdministrationIdentifier $program,
        UserIdentifier $user_identifier,
    ): self {
        $aggregated_teams_id    = $teams_of_program_searcher->searchTeamIdsOfProgram($program->id);
        $existing_programs_id   = $all_program_searcher->getAllPrograms();
        $projects_user_is_admin = $retrieve_project_user_is_admin->getProjectsUserIsAdmin($user_identifier);

        $potential_teams = [];

        foreach ($projects_user_is_admin as $project_user_is_admin) {
            if (
                $program->id !== $project_user_is_admin->getId()
                && ! \in_array($project_user_is_admin->getId(), $aggregated_teams_id, true)
                && ! \in_array($project_user_is_admin->getId(), $existing_programs_id, true)
            ) {
                $potential_teams[] = PotentialTeam::fromId(
                    $project_user_is_admin->getId(),
                    $project_user_is_admin->getProjectLabel(),
                    $project_user_is_admin->getProjectIcon()
                );
            }
        }

        return new self($potential_teams);
    }

    /**
     * @return PotentialTeam[]
     */
    public function getPotentialTeams(): array
    {
        return $this->potential_teams;
    }
}
