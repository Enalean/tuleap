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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team;

use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;

final class TeamProjectsCollectionBuilder
{
    /**
     * @var ProgramStore
     */
    private $program_store;
    /**
     * @var BuildProject
     */
    private $project_data_adapter;

    public function __construct(ProgramStore $program_store, BuildProject $project_data_adapter)
    {
        $this->program_store        = $program_store;
        $this->project_data_adapter = $project_data_adapter;
    }

    public function getTeamProjectForAGivenProgramProject(ProgramIdentifier $program): TeamProjectsCollection
    {
        $program_project_id = $program->getID();
        $team_projects      = [];
        foreach ($this->program_store->getTeamProjectIdsForGivenProgramProject($program_project_id) as $row) {
            $team_projects[] = $this->project_data_adapter->buildFromId($row['team_project_id']);
        }

        return new TeamProjectsCollection($team_projects);
    }
}
