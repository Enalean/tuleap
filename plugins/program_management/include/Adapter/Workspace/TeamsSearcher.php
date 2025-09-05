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
use Tuleap\Project\Sidebar\SearchLinkedProjects;

final class TeamsSearcher implements SearchLinkedProjects
{
    public function __construct(
        private SearchTeamsOfProgram $team_ids_searcher,
        private RetrieveFullProject $retrieve_full_project,
    ) {
    }

    /**
     * @return \Project[]
     */
    #[\Override]
    public function searchLinkedProjects(\Project $source_project): array
    {
        $team_ids      = $this->team_ids_searcher->searchTeamIdsOfProgram((int) $source_project->getID());
        $team_projects = [];
        foreach ($team_ids as $team_id) {
            $team_projects[] = $this->retrieve_full_project->getProject($team_id);
        }
        return $team_projects;
    }
}
