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
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
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
        RetrieveProject $project_manager,
        SearchTeamsOfProgram $teams_of_program_searcher,
        AllProgramSearcher $all_program_searcher,
        ProgramForAdministrationIdentifier $program,
        UserIdentifier $user_identifier
    ): self {
        $aggregated_teams_id    = $teams_of_program_searcher->searchTeamIdsOfProgram($program->id);
        $existing_programs_id   = $all_program_searcher->getAllPrograms();
        $projects_user_is_admin = $project_manager->getProjectsUserIsAdmin($user_identifier);

        $potential_teams = [];

        foreach ($projects_user_is_admin as $project_user_is_admin) {
            if (
                $program->id !== (int) $project_user_is_admin->getID()
                && ! \in_array((int) $project_user_is_admin->getID(), $aggregated_teams_id, true)
                && ! \in_array((int) $project_user_is_admin->getID(), $existing_programs_id, true)
            ) {
                $potential_teams[] = PotentialTeam::fromId(
                    (int) $project_user_is_admin->getID(),
                    $project_user_is_admin->getPublicName()
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
