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

namespace Tuleap\MultiProjectBacklog\Aggregator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Project;
use ProjectManager;

class ReadOnlyAggregatorAdminURLBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AggregatorDao
     */
    private $aggregator_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var ReadOnlyAggregatorAdminURLBuilder
     */
    private $url_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $planning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregator_dao  = Mockery::mock(AggregatorDao::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->url_builder = new ReadOnlyAggregatorAdminURLBuilder(
            $this->aggregator_dao,
            $this->project_manager
        );

        $this->planning = new Planning(
            43,
            'Planning 01',
            102,
            'Backlog',
            'Plan'
        );
    }

    public function testItReturnsTheReaOnlyURL(): void
    {
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->once()->with(102)->andReturnTrue();

        $project = Mockery::mock(Project::class)->shouldReceive('getUnixName')->once()->andReturn('proj01')->getMock();
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
        $root_planning = new Planning(
            42,
            'Planning Root 01',
            102,
            'Backlog',
            'Plan'
        );

        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->once()->with(102)->andReturnTrue();

        $url = $this->url_builder->buildURL(
            $this->planning,
            $root_planning
        );

        $this->assertNull($url);
    }

    public function testItReturnsNullIfProjectIsNotAggregator(): void
    {
        $this->aggregator_dao->shouldReceive('isProjectAnAggregatorProject')->once()->with(102)->andReturnFalse();

        $url = $this->url_builder->buildURL(
            $this->planning,
            $this->planning
        );

        $this->assertNull($url);
    }
}
