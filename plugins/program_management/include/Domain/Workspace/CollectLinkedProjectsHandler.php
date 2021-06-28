<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Workspace;

use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Project\Sidebar\LinkedProjectsCollection;

final class CollectLinkedProjectsHandler
{
    private VerifyIsProgram $program_verifier;
    private TeamsSearcher $teams_searcher;
    private CheckProjectAccess $access_checker;
    private VerifyIsTeam $team_verifier;
    private ProgramsSearcher $programs_searcher;

    public function __construct(
        VerifyIsProgram $program_verifier,
        TeamsSearcher $teams_searcher,
        CheckProjectAccess $access_checker,
        VerifyIsTeam $team_verifier,
        ProgramsSearcher $programs_searcher
    ) {
        $this->program_verifier  = $program_verifier;
        $this->teams_searcher    = $teams_searcher;
        $this->access_checker    = $access_checker;
        $this->team_verifier     = $team_verifier;
        $this->programs_searcher = $programs_searcher;
    }

    public function handle(CollectLinkedProjects $event): void
    {
        $source_project_id = (int) $event->getSourceProject()->getID();
        if ($this->program_verifier->isAProgram($source_project_id)) {
            $this->addAggregatedProjects($event);
            return;
        }
        if ($this->team_verifier->isATeam($source_project_id)) {
            $this->addParentProjects($event);
        }
    }

    private function addAggregatedProjects(CollectLinkedProjects $event): void
    {
        $collection = LinkedProjectsCollection::fromSourceProject(
            $this->teams_searcher,
            $this->access_checker,
            $event->getSourceProject(),
            $event->getCurrentUser()
        );
        $event->addChildrenProjects($collection);
    }

    private function addParentProjects(CollectLinkedProjects $event): void
    {
        $collection = LinkedProjectsCollection::fromSourceProject(
            $this->programs_searcher,
            $this->access_checker,
            $event->getSourceProject(),
            $event->getCurrentUser()
        );
        $event->addParentProjects($collection);
    }
}
