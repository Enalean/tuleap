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

use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramsOfTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

final class CollectLinkedProjectsHandlerTest extends TestCase
{
    private VerifyIsProgramStub $program_verifier;
    private TeamsSearcher $teams_searcher;
    private CollectLinkedProjects $event;
    private CheckProjectAccessStub $access_checker;
    private VerifyIsTeamStub $team_verifier;
    private ProgramsSearcher $programs_searcher;

    protected function setUp(): void
    {
        $this->program_verifier  = VerifyIsProgramStub::withNotValidProgram();
        $this->team_verifier     = VerifyIsTeamStub::withNotValidTeam();
        $this->teams_searcher    = new TeamsSearcher(
            SearchTeamsOfProgramStub::buildTeams(),
            RetrieveProjectStub::withValidProjects(),
        );
        $this->programs_searcher = new ProgramsSearcher(
            SearchProgramsOfTeamStub::buildPrograms(),
            RetrieveProjectStub::withValidProjects(),
        );
        $this->access_checker    = CheckProjectAccessStub::withValidAccess();

        $source_project = ProjectTestBuilder::aProject()->build();
        $user           = UserTestBuilder::aUser()->build();
        $this->event    = new CollectLinkedProjects($source_project, $user);
    }

    private function getHandler(): CollectLinkedProjectsHandler
    {
        return new CollectLinkedProjectsHandler(
            $this->program_verifier,
            $this->teams_searcher,
            $this->access_checker,
            $this->team_verifier,
            $this->programs_searcher
        );
    }

    public function testItBuildsACollectionOfTeamProjectsAndMutatesTheEvent(): void
    {
        $this->setProgramContext();
        $this->getHandler()->handle($this->event);

        $collection = $this->event->getChildrenProjects();
        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection->getProjects());
    }

    public function testWhenUserCannotAccessAnyTeamItDoesNotMutateTheEvent(): void
    {
        $this->setProgramContext();
        $this->access_checker = CheckProjectAccessStub::withPrivateProjectWithoutAccess();

        $this->getHandler()->handle($this->event);

        self::assertTrue($this->event->getChildrenProjects()->isEmpty());
        self::assertTrue($this->event->getParentProjects()->isEmpty());
    }

    public function testItBuildsACollectionOfProgramProjectsAndMutatesTheEvent(): void
    {
        $this->setTeamContext();
        $this->getHandler()->handle($this->event);

        $collection = $this->event->getParentProjects();
        self::assertFalse($collection->isEmpty());
        self::assertCount(2, $collection->getProjects());
    }

    public function testWhenUserCannotAccessAnyProgramItDoesNotMutateTheEvent(): void
    {
        $this->setTeamContext();
        $this->access_checker = CheckProjectAccessStub::withPrivateProjectWithoutAccess();

        $this->getHandler()->handle($this->event);

        self::assertTrue($this->event->getChildrenProjects()->isEmpty());
        self::assertTrue($this->event->getParentProjects()->isEmpty());
    }

    public function testGivenAProjectThatIsNeitherAProgramNorATeamItDoesNotMutateTheEvent(): void
    {
        $this->getHandler()->handle($this->event);

        self::assertTrue($this->event->getChildrenProjects()->isEmpty());
        self::assertTrue($this->event->getParentProjects()->isEmpty());
    }

    private function setProgramContext(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withValidProgram();
        $red_team               = ProjectTestBuilder::aProject()->build();
        $blue_team              = ProjectTestBuilder::aProject()->build();
        $this->teams_searcher   = new TeamsSearcher(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            RetrieveProjectStub::withValidProjects($red_team, $blue_team)
        );
    }

    private function setTeamContext(): void
    {
        $this->team_verifier     = VerifyIsTeamStub::withValidTeam();
        $red_program             = ProjectTestBuilder::aProject()->build();
        $blue_program            = ProjectTestBuilder::aProject()->build();
        $this->programs_searcher = new ProgramsSearcher(
            SearchProgramsOfTeamStub::buildPrograms(110, 111),
            RetrieveProjectStub::withValidProjects($red_program, $blue_program)
        );
    }
}
