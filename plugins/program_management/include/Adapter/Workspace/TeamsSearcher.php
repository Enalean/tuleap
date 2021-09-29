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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\Project\Sidebar\SearchLinkedProjects;

final class TeamsSearcher implements SearchLinkedProjects
{
    private SearchTeamsOfProgram $team_ids_searcher;
    private RetrieveProject $project_retriever;

    public function __construct(
        SearchTeamsOfProgram $team_ids_searcher,
        RetrieveProject $project_retriever
    ) {
        $this->team_ids_searcher = $team_ids_searcher;
        $this->project_retriever = $project_retriever;
    }

    /**
     * @return \Project[]
     */
    public function searchLinkedProjects(\Project $source_project): array
    {
        $team_ids      = $this->team_ids_searcher->searchTeamIdsOfProgram((int) $source_project->getID());
        $team_projects = [];
        foreach ($team_ids as $team_id) {
            $team_projects[] = $this->project_retriever->getProjectWithId($team_id);
        }
        return $team_projects;
    }
}
