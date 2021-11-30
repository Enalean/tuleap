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

use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\ProjectReference;

/**
 * I am a collection of Team Projects. I can be empty.
 * @psalm-immutable
 */
final class TeamProjectsCollection
{
    /**
     * @var ProjectReference[]
     */
    private array $team_projects;

    /**
     * @param ProjectReference[] $team_projects
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
     * @return ProjectReference[]
     */
    public function getTeamProjects(): array
    {
        return $this->team_projects;
    }

    public static function fromProgramIdentifier(
        SearchTeamsOfProgram $teams_searcher,
        RetrieveProjectReference $project_builder,
        ProgramIdentifier $program,
    ): self {
        return self::buildFromProjectId($teams_searcher, $project_builder, $program->getId());
    }

    public static function fromProgramForAdministration(
        SearchTeamsOfProgram $teams_searcher,
        RetrieveProjectReference $project_builder,
        ProgramForAdministrationIdentifier $program,
    ): self {
        return self::buildFromProjectId($teams_searcher, $project_builder, $program->id);
    }

    private static function buildFromProjectId(
        SearchTeamsOfProgram $teams_searcher,
        RetrieveProjectReference $project_builder,
        int $program_project_id,
    ): self {
        $team_projects = [];
        foreach ($teams_searcher->searchTeamIdsOfProgram($program_project_id) as $team_id) {
            $team_projects[] = $project_builder->buildFromId($team_id);
        }

        return new self($team_projects);
    }
}
