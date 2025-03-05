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
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TeamsSearcherTest extends TestCase
{
    private SearchTeamsOfProgram $team_ids_searcher;
    private RetrieveFullProject $retrieve_full_project;

    protected function setUp(): void
    {
        $this->team_ids_searcher     = SearchTeamsOfProgramStub::withTeamIds(102, 103);
        $this->retrieve_full_project = RetrieveFullProjectStub::withoutProject();
    }

    private function getSearcher(): TeamsSearcher
    {
        return new TeamsSearcher($this->team_ids_searcher, $this->retrieve_full_project);
    }

    public function testItReturnsTheTeamProjectsOfAProgram(): void
    {
        $team_red  = ProjectTestBuilder::aProject()->withId(102)->build();
        $team_blue = ProjectTestBuilder::aProject()->withId(103)->build();

        $this->retrieve_full_project = RetrieveFullProjectStub::withProjects($team_red, $team_blue);

        $program = ProjectTestBuilder::aProject()->withId(101)->build();
        $teams   = $this->getSearcher()->searchLinkedProjects($program);
        self::assertCount(2, $teams);
        self::assertContains($team_red, $teams);
        self::assertContains($team_blue, $teams);
    }
}
