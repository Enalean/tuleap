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
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Project;

/**
 * @psalm-immutable
 */
final class TeamProjectsCollection
{
    /**
     * @var Project[]
     */
    private array $team_projects;

    /**
     * @param Project[] $team_projects
     */
    private function __construct(array $team_projects)
    {
        $this->team_projects = $team_projects;
    }

    public function isEmpty(): bool
    {
        return empty($this->team_projects);
    }

    /**
     * @return Project[]
     */
    public function getTeamProjects(): array
    {
        return $this->team_projects;
    }

    public static function fromProgramIdentifier(
        SearchTeamsOfProgram $teams_searcher,
        BuildProject $project_data_adapter,
        ProgramIdentifier $program
    ): self {
        $program_project_id = $program->getID();
        $team_projects      = [];
        foreach ($teams_searcher->searchTeamIdsOfProgram($program_project_id) as $team_id) {
            $team_projects[] = $project_data_adapter->buildFromId($team_id);
        }

        return new self($team_projects);
    }
}
