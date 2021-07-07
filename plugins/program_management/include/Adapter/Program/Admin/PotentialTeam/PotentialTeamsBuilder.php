<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\BuildPotentialTeams;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;

final class PotentialTeamsBuilder implements BuildPotentialTeams
{
    private \ProjectManager $project_manager;
    private SearchTeamsOfProgram $teams_of_program_searcher;

    public function __construct(\ProjectManager $project_manager, SearchTeamsOfProgram $teams_of_program_searcher)
    {
        $this->project_manager           = $project_manager;
        $this->teams_of_program_searcher = $teams_of_program_searcher;
    }

    /**
     * @return PotentialTeam[]
     */
    public function buildPotentialTeams(int $project_id, \PFUser $user): array
    {
        $aggregated_teams_id    = $this->teams_of_program_searcher->searchTeamIdsOfProgram($project_id);
        $projects_user_is_admin = $this->project_manager->getProjectsUserIsAdmin($user);

        $potentially_teams = [];

        foreach ($projects_user_is_admin as $project_user_is_admin) {
            if ($project_id !== (int) $project_user_is_admin->getID() && ! \in_array((int) $project_user_is_admin->getID(), $aggregated_teams_id, true)) {
                $potentially_teams[] = PotentialTeam::fromId((int) $project_user_is_admin->getID(), $project_user_is_admin->getPublicName());
            }
        }

        return $potentially_teams;
    }
}
