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
use Tuleap\ProgramManagement\Adapter\Workspace\TeamsSearcher;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramsOfTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectLinkedProjectsProxyTest extends TestCase
{
    private TeamsSearcher $teams_searcher;
    private CheckProjectAccessStub $access_checker;
    private \Project $source_project;
    private ProgramsSearcher $programs_searcher;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->source_project = ProjectTestBuilder::aProject()->withId(101)->build();
        $linked_project       = ProjectTestBuilder::aProject()->withId(102)->build();

        $retrieve_full_project = RetrieveFullProjectStub::withProjects($this->source_project, $linked_project);

        $this->teams_searcher    = new TeamsSearcher(
            SearchTeamsOfProgramStub::withTeamIds((int) $linked_project->getID()),
            $retrieve_full_project
        );
        $this->programs_searcher = new ProgramsSearcher(
            SearchProgramsOfTeamStub::buildPrograms((int) $this->source_project->getID()),
            $retrieve_full_project
        );
        $this->access_checker    = CheckProjectAccessStub::withValidAccess();

        $this->user = UserTestBuilder::aUser()->build();
    }

    public function testItBuildsACollectionOfTeamProjects(): void
    {
        $this->setProgramContext();
        $original_event = new CollectLinkedProjects($this->source_project, $this->user);

        $proxy = CollectLinkedProjectsProxy::fromCollectLinkedProjects(
            $this->teams_searcher,
            $this->access_checker,
            $this->programs_searcher,
            $original_event
        );
        $proxy->addTeams();

        $collection = $original_event->getChildrenProjects();
        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection->getProjects());
    }

    public function testItBuildsACollectionOfProgramProjects(): void
    {
        $this->setTeamContext();
        $original_event = new CollectLinkedProjects($this->source_project, $this->user);
        $proxy          = CollectLinkedProjectsProxy::fromCollectLinkedProjects(
            $this->teams_searcher,
            $this->access_checker,
            $this->programs_searcher,
            $original_event
        );
        $proxy->addPrograms();

        $collection = $original_event->getParentProjects();
        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection->getProjects());
    }

    private function setProgramContext(): void
    {
        $red_team              = ProjectTestBuilder::aProject()->withId(103)->build();
        $blue_team             = ProjectTestBuilder::aProject()->withId(104)->build();
        $retrieve_full_project = RetrieveFullProjectStub::withProjects($red_team, $blue_team);
        $this->teams_searcher  = new TeamsSearcher(
            SearchTeamsOfProgramStub::withTeamIds(103, 104),
            $retrieve_full_project
        );
    }

    private function setTeamContext(): void
    {
        $red_program             = ProjectTestBuilder::aProject()->withId(110)->build();
        $blue_program            = ProjectTestBuilder::aProject()->withId(111)->build();
        $retrieve_full_project   = RetrieveFullProjectStub::withProjects($red_program, $blue_program);
        $this->programs_searcher = new ProgramsSearcher(
            SearchProgramsOfTeamStub::buildPrograms(110, 111),
            $retrieve_full_project
        );
    }
}
