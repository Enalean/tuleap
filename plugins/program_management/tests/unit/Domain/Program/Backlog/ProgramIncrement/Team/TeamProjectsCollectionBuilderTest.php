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

use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamProjectsCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TeamProjectsCollectionBuilder $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramStore
     */
    private $program_store;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        $this->program_store   = $this->createStub(ProgramStore::class);
        $this->project_manager = $this->createStub(\ProjectManager::class);
        $project_data_adapter  = new ProjectAdapter($this->project_manager);
        $this->builder         = new TeamProjectsCollectionBuilder(
            $this->program_store,
            $project_data_adapter
        );
    }

    public function testItBuildsACollectionOfTeamProjects(): void
    {
        $this->program_store->method('getTeamProjectIdsForGivenProgramProject')
            ->willReturn([['team_project_id' => 124], ['team_project_id' => 125],]);

        $team_project_01 = ProjectTestBuilder::aProject()->withId(124)->build();
        $team_project_02 = ProjectTestBuilder::aProject()->withId(125)->build();
        $this->project_manager->method('getProject')->willReturnOnConsecutiveCalls($team_project_01, $team_project_02);

        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 123, $user);

        $collection = $this->builder->getTeamProjectForAGivenProgramProject($program);

        self::assertCount(2, $collection->getTeamProjects());
        self::assertEquals(
            [ProjectAdapter::build($team_project_01), ProjectAdapter::build($team_project_02)],
            $collection->getTeamProjects()
        );
    }

    public function testItReturnsAnEmptyCollectionIfDatabaseIsInconsistent(): void
    {
        $this->program_store->method('getTeamProjectIdsForGivenProgramProject')
            ->willReturn([]);

        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 123, $user);

        $collection = $this->builder->getTeamProjectForAGivenProgramProject($program);
        self::assertEmpty($collection->getTeamProjects());
    }
}
