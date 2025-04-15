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

use Tuleap\ProgramManagement\Adapter\Events\CollectLinkedProjectsProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramsSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\TeamsSearcher;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramsOfTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProgramServiceIsEnabledStub;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectLinkedProjectsHandlerTest extends TestCase
{
    private CollectLinkedProjects $original_event;
    private CheckProjectAccessStub $access_checker;

    protected function setUp(): void
    {
        $source_project       = ProjectTestBuilder::aProject()->build();
        $this->original_event = new CollectLinkedProjects($source_project, UserTestBuilder::aUser()->build());
        $this->access_checker = CheckProjectAccessStub::withValidAccess();
    }

    public function testItBuildsACollectionOfTeamProjects(): void
    {
        $handler = new CollectLinkedProjectsHandler(
            VerifyIsProgramStub::withValidProgram(),
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProgramServiceIsEnabledStub::withProgramServiceEnabled(),
        );

        $event = $this->buildEventForProgram();
        $handler->handle($event);

        $collection = $this->original_event->getChildrenProjects();
        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection->getProjects());
        self::assertTrue($this->original_event->canAggregateProjects());
    }

    public function testItBuildsACollectionOfProgramProjects(): void
    {
        $handler = new CollectLinkedProjectsHandler(
            VerifyIsProgramStub::withNotValidProgram(),
            VerifyIsTeamStub::withValidTeam(),
            VerifyProgramServiceIsEnabledStub::withProgramServiceEnabled(),
        );

        $event = $this->buildEventForTeam();
        $handler->handle($event);

        $collection = $this->original_event->getParentProjects();
        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection->getProjects());
        self::assertTrue($this->original_event->canAggregateProjects());
    }

    public function testDoesNothingWhenProjectIsNotAProgramAndNotATeam(): void
    {
        $handler = new CollectLinkedProjectsHandler(
            VerifyIsProgramStub::withNotValidProgram(),
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProgramServiceIsEnabledStub::withProgramServiceEnabled(),
        );

        $event = $this->buildEventForTeam();
        $handler->handle($event);

        $collection = $this->original_event->getParentProjects();
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getProjects());

        $collection = $this->original_event->getChildrenProjects();
        self::assertTrue($collection->isEmpty());
        self::assertEmpty($collection->getProjects());

        self::assertTrue($this->original_event->canAggregateProjects());
    }

    public function testWhenProgramServiceIsDisabled(): void
    {
        $handler = new CollectLinkedProjectsHandler(
            VerifyIsProgramStub::withNotValidProgram(),
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProgramServiceIsEnabledStub::withProgramServiceDisabled(),
        );

        $event = $this->buildEventForTeam();
        $handler->handle($event);

        self::assertFalse($this->original_event->canAggregateProjects());
    }

    private function buildEventForProgram(): CollectLinkedProjectsProxy
    {
        $red_team       = ProjectTestBuilder::aProject()->withId(103)->build();
        $blue_team      = ProjectTestBuilder::aProject()->withId(104)->build();
        $teams_searcher = new TeamsSearcher(
            SearchTeamsOfProgramStub::withTeamIds(103, 104),
            RetrieveFullProjectStub::withProjects($red_team, $blue_team)
        );

        $programs_searcher = new ProgramsSearcher(
            SearchProgramsOfTeamStub::withNoPrograms(),
            RetrieveFullProjectStub::withoutProject()
        );

        return CollectLinkedProjectsProxy::fromCollectLinkedProjects(
            $teams_searcher,
            $this->access_checker,
            $programs_searcher,
            $this->original_event
        );
    }

    private function buildEventForTeam(): CollectLinkedProjectsProxy
    {
        $red_program    = ProjectTestBuilder::aProject()->withId(110)->build();
        $blue_program   = ProjectTestBuilder::aProject()->withId(111)->build();
        $teams_searcher = new TeamsSearcher(
            SearchTeamsOfProgramStub::withNoTeams(),
            RetrieveFullProjectStub::withoutProject()
        );

        $programs_searcher = new ProgramsSearcher(
            SearchProgramsOfTeamStub::buildPrograms(110, 111),
            RetrieveFullProjectStub::withProjects($red_program, $blue_program)
        );

        return CollectLinkedProjectsProxy::fromCollectLinkedProjects(
            $teams_searcher,
            $this->access_checker,
            $programs_searcher,
            $this->original_event
        );
    }
}
