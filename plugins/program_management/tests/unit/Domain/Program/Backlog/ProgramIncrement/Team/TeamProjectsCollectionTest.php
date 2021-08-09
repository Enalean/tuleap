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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamProjectsCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchTeamsOfProgramStub $search_teams;
    private BuildProjectStub $project_builder;
    private ProgramIdentifier $program;

    protected function setUp(): void
    {
        $this->search_teams    = SearchTeamsOfProgramStub::buildTeams(103, 125);
        $this->project_builder = new BuildProjectStub();
        $this->program         = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            100,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFromProgramIdentifier(): void
    {
        $collection = TeamProjectsCollection::fromProgramIdentifier(
            $this->search_teams,
            $this->project_builder,
            $this->program
        );
        self::assertSame(103, $collection->getTeamProjects()[0]->getId());
        self::assertSame(125, $collection->getTeamProjects()[1]->getId());
        self::assertFalse($collection->isEmpty());
    }

    public function testItBuildsFromProgramForAdministration(): void
    {
        $program    = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            UserTestBuilder::aUser()->build(),
            ProjectTestBuilder::aProject()->withId(101)->build()
        );
        $collection = TeamProjectsCollection::fromProgramForAdministration(
            $this->search_teams,
            $this->project_builder,
            $program
        );
        self::assertSame(103, $collection->getTeamProjects()[0]->getId());
        self::assertSame(125, $collection->getTeamProjects()[1]->getId());
        self::assertFalse($collection->isEmpty());
    }

    public function testIsEmptyReturnsTrue(): void
    {
        $collection = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(),
            $this->project_builder,
            $this->program
        );
        self::assertTrue($collection->isEmpty());
    }
}
