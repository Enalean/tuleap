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

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TeamProjectsCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchTeamsOfProgramStub $search_teams;
    private RetrieveProjectReferenceStub $project_builder;
    private ProgramIdentifier $program;

    #[\Override]
    protected function setUp(): void
    {
        $this->search_teams    = SearchTeamsOfProgramStub::withTeamIds(103, 125);
        $this->project_builder = RetrieveProjectReferenceStub::withProjects(
            ProjectReferenceStub::withId(103),
            ProjectReferenceStub::withId(125),
        );
        $this->program         = ProgramIdentifierBuilder::build();
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
        $program    = ProgramForAdministrationIdentifierBuilder::build();
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
            SearchTeamsOfProgramStub::withNoTeams(),
            $this->project_builder,
            $this->program
        );
        self::assertTrue($collection->isEmpty());
    }
}
