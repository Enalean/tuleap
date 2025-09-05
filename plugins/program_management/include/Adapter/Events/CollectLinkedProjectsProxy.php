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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\ProgramManagement\Adapter\Workspace\ProgramsSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\TeamsSearcher;
use Tuleap\ProgramManagement\Domain\Events\CollectLinkedProjectsEvent;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Project\Sidebar\LinkedProjectsCollection;

final class CollectLinkedProjectsProxy implements CollectLinkedProjectsEvent
{
    private function __construct(
        private TeamsSearcher $teams_searcher,
        private CheckProjectAccess $access_checker,
        private ProgramsSearcher $programs_searcher,
        private ProjectIdentifier $source_project,
        private CollectLinkedProjects $linked_projects,
    ) {
    }

    public static function fromCollectLinkedProjects(
        TeamsSearcher $teams_searcher,
        CheckProjectAccess $access_checker,
        ProgramsSearcher $programs_searcher,
        CollectLinkedProjects $linked_projects,
    ): self {
        return new self(
            $teams_searcher,
            $access_checker,
            $programs_searcher,
            ProjectProxy::buildFromProject($linked_projects->getSourceProject()),
            $linked_projects,
        );
    }

    /**
     * @psalm-readonly
     */
    #[\Override]
    public function getSourceProject(): ProjectIdentifier
    {
        return $this->source_project;
    }

    #[\Override]
    public function addTeams(): void
    {
        $collection = LinkedProjectsCollection::fromSourceProject(
            $this->teams_searcher,
            $this->access_checker,
            $this->linked_projects->getSourceProject(),
            $this->linked_projects->getCurrentUser()
        );
        $this->linked_projects->addChildrenProjects($collection);
    }

    #[\Override]
    public function addPrograms(): void
    {
        $collection = LinkedProjectsCollection::fromSourceProject(
            $this->programs_searcher,
            $this->access_checker,
            $this->linked_projects->getSourceProject(),
            $this->linked_projects->getCurrentUser()
        );
        $this->linked_projects->addParentProjects($collection);
    }

    #[\Override]
    public function projectCanAggregateProjects(): void
    {
        $this->linked_projects->projectCanAggregateProjects();
    }
}
