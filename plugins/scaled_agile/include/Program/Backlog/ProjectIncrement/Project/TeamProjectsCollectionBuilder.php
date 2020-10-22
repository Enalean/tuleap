<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project;

use Project;
use ProjectManager;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;

class TeamProjectsCollectionBuilder
{
    /**
     * @var ProgramDao
     */
    private $program_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(ProgramDao $program_dao, ProjectManager $project_manager)
    {
        $this->program_dao     = $program_dao;
        $this->project_manager = $project_manager;
    }

    public function getTeamProjectForAGivenProgramProject(Project $project): TeamProjectsCollection
    {
        $program_project_id = (int) $project->getID();
        $team_projects  = [];
        foreach ($this->program_dao->getTeamProjectIdsForGivenProgramProject($program_project_id) as $row) {
            $team_projects[] = $this->project_manager->getProject($row['team_project_id']);
        }

        return new TeamProjectsCollection($team_projects);
    }
}
