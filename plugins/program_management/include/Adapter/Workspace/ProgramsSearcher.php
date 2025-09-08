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

use Tuleap\ProgramManagement\Domain\Team\SearchProgramsOfTeam;
use Tuleap\Project\Sidebar\SearchLinkedProjects;

final class ProgramsSearcher implements SearchLinkedProjects
{
    public function __construct(
        private SearchProgramsOfTeam $program_ids_searcher,
        private RetrieveFullProject $retrieve_full_project,
    ) {
    }

    /**
     * @return \Project[]
     */
    #[\Override]
    public function searchLinkedProjects(\Project $source_project): array
    {
        $program_ids      = $this->program_ids_searcher->searchProgramIdsOfTeam((int) $source_project->getID());
        $program_projects = [];
        foreach ($program_ids as $program_id) {
            $program_projects[] = $this->retrieve_full_project->getProject($program_id);
        }
        return $program_projects;
    }
}
