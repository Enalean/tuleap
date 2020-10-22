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

namespace Tuleap\ScaledAgile\Program\Administration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use NullTracker;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class ReadOnlyProgramAdminURLBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProgramDao
     */
    private $program_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var ReadOnlyProgramAdminURLBuilder
     */
    private $url_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningData
     */
    private $planning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program_dao     = Mockery::mock(ProgramDao::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->url_builder = new ReadOnlyProgramAdminURLBuilder(
            $this->program_dao,
            $this->project_manager
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject(new Project(['group_id' => 102]))->build();
        $this->planning = new PlanningData($tracker, 43, 'Planning 01', []);
    }

    public function testItReturnsTheReaOnlyURL(): void
    {
        $this->program_dao->shouldReceive('isProjectAProgramProject')->once()->with(102)->andReturnTrue();

        $project = new Project(['group_id' => 101, 'unix_group_name' => 'proj01']);
        $this->project_manager->shouldReceive('getProject')->once()->with(102)->andReturn($project);

        $url = $this->url_builder->buildURL(
            $this->planning,
            $this->planning
        );

        $this->assertSame(
            "/project/proj01/backlog/admin/43",
            $url
        );
    }

    public function testItReturnsNullIfNoRootPlanning(): void
    {
        $url = $this->url_builder->buildURL(
            $this->planning,
            null
        );

        $this->assertNull($url);
    }

    public function testItReturnsNullIfPlanningIsNotRootPlanning(): void
    {
        $root_planning = new PlanningData(new NullTracker(), 42, 'Planning Root 01', []);

        $this->program_dao->shouldReceive('isProjectAProgramProject')->once()->with(102)->andReturnTrue();

        $url = $this->url_builder->buildURL(
            $this->planning,
            $root_planning
        );

        $this->assertNull($url);
    }

    public function testItReturnsNullIfProjectIsNotProgram(): void
    {
        $this->program_dao->shouldReceive('isProjectAProgramProject')->once()->with(102)->andReturnFalse();

        $url = $this->url_builder->buildURL(
            $this->planning,
            $this->planning
        );

        $this->assertNull($url);
    }
}
