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
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ReadOnlyProgramAdminURLBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tuleap\ScaledAgile\ProjectData
     */
    private $project_data;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProgramDao
     */
    private $program_dao;

    /**
     * @var ReadOnlyProgramAdminURLBuilder
     */
    private $url_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program_dao = Mockery::mock(ProgramDao::class);
        $this->url_builder = new ReadOnlyProgramAdminURLBuilder($this->program_dao);

        $this->project_data = ProjectDataAdapter::build(
            new Project(['group_id' => 102, 'group_name' => 'Team A', 'unix_group_name' => 'proj01'])
        );
    }

    public function testItReturnsTheReadOnlyURL(): void
    {
        $this->program_dao->shouldReceive('isProjectAProgramProject')
            ->once()
            ->with($this->project_data->getId())
            ->andReturnTrue();

        $planning = $this->buildPlanningData($this->buildTrackerData(), 43);
        $url = $this->url_builder->buildURL(
            $planning,
            $planning
        );

        $this->assertSame(
            "/project/proj01/backlog/admin/43",
            $url
        );
    }

    public function testItReturnsNullIfNoRootPlanning(): void
    {
        $planning = $this->buildPlanningData($this->buildTrackerData(), 43);
        $url = $this->url_builder->buildURL(
            $planning,
            null
        );

        $this->assertNull($url);
    }

    public function testItReturnsNullIfPlanningIsNotRootPlanning(): void
    {
        $planning = $this->buildPlanningData($this->buildTrackerData(), 43);
        $root_planning = $this->buildPlanningData(TrackerDataAdapter::build(new NullTracker()), 1);

        $this->program_dao->shouldReceive('isProjectAProgramProject')
            ->once()
            ->with($this->project_data->getID())
            ->andReturnTrue();

        $url = $this->url_builder->buildURL(
            $planning,
            $root_planning
        );

        $this->assertNull($url);
    }

    public function testItReturnsNullIfProjectIsNotProgram(): void
    {
        $planning = $this->buildPlanningData($this->buildTrackerData(), 43);
        $this->program_dao->shouldReceive('isProjectAProgramProject')
            ->once()
            ->with($this->project_data->getId())
            ->andReturnFalse();

        $url = $this->url_builder->buildURL(
            $planning,
            $planning
        );

        $this->assertNull($url);
    }

    private function buildTrackerData(): TrackerData
    {
        return TrackerDataAdapter::build(
            TrackerTestBuilder::aTracker()
                ->withId(1)
                ->withProject(new Project(['group_id' => $this->project_data->getId()]))
                ->build()
        );
    }

    private function buildPlanningData(TrackerData $tracker_data, int $planning_id): PlanningData
    {
        return new PlanningData($tracker_data, $planning_id, 'Planning 01', [], $this->project_data);
    }
}
