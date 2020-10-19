<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;

class TeamProjectsCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TeamProjectsCollectionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProgramDao
     */
    private $program_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program_dao  = Mockery::mock(ProgramDao::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->builder = new TeamProjectsCollectionBuilder(
            $this->program_dao,
            $this->project_manager
        );

        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('123')->getMock();
    }

    public function testItBuildsACollectionOfTeamProjects(): void
    {
        $this->program_dao->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->once()
            ->with(123)
            ->andReturn([
                ['team_project_id' => 124],
                ['team_project_id' => 125],
            ]);

        $team_project_01 = Mockery::mock(Project::class);
        $team_project_02 = Mockery::mock(Project::class);

        $this->project_manager->shouldReceive('getProject')
            ->with(124)
            ->once()
            ->andReturn($team_project_01);

        $this->project_manager->shouldReceive('getProject')
            ->with(125)
            ->once()
            ->andReturn($team_project_02);

        $collection = $this->builder->getTeamProjectForAGivenProgramProject($this->project);

        $this->assertCount(2, $collection->getTeamProjects());
        $this->assertSame(
            [$team_project_01, $team_project_02],
            $collection->getTeamProjects()
        );
    }

    public function testItReturnsAnEmptyCollectionIfProvidedProjectIsNotProgram(): void
    {
        $this->program_dao->shouldReceive('getTeamProjectIdsForGivenProgramProject')
            ->once()
            ->with(123)
            ->andReturn([]);

        $collection = $this->builder->getTeamProjectForAGivenProgramProject($this->project);

        $this->assertEmpty($collection->getTeamProjects());
    }
}
