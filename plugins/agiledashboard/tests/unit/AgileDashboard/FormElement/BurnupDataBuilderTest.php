<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ColinODell\PsrTestLogger\TestLogger;
use Tracker;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsInfo;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class BurnupDataBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private BurnupDataBuilder $burnup_data_builder;

    private TestLogger $logger;
    private $burnup_cache_checker;
    private $chart_configuration_value_retriever;
    private $burnup_cache_dao;
    private $burnup_calculator;

    /**
     * @var Mockery\MockInterface|CountElementsCacheDao
     */
    private $count_cache_dao;

    /**
     * @var Mockery\MockInterface|CountElementsCalculator
     */
    private $count_calculator;

    /**
     * @var Mockery\MockInterface|CountElementsModeChecker
     */
    private $mode_checker;
    private Tracker $artifact_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger                              = new TestLogger();
        $this->burnup_cache_checker                = Mockery::mock(BurnupCacheChecker::class);
        $this->chart_configuration_value_retriever = Mockery::mock(ChartConfigurationValueRetriever::class);
        $this->burnup_cache_dao                    = Mockery::mock(BurnupCacheDao::class);
        $this->burnup_calculator                   = Mockery::mock(BurnupCalculator::class);
        $this->count_cache_dao                     = Mockery::mock(CountElementsCacheDao::class);
        $this->count_calculator                    = Mockery::mock(CountElementsCalculator::class);
        $this->mode_checker                        = Mockery::mock(CountElementsModeChecker::class);
        $planning_dao                              = $this->createMock(PlanningDao::class);
        $planning_factory                          = $this->createMock(\PlanningFactory::class);

        $this->artifact_tracker = TrackerTestBuilder::aTracker()->withId(10)->withProject(ProjectTestBuilder::aProject()->build())->build();

        $planning_dao->method('searchByMilestoneTrackerId')->willReturn(['id' => $this->artifact_tracker->getId()]);
        $planning_factory->method('getBacklogTrackersIds')->willReturn([[$this->artifact_tracker->getId()]]);

        $this->burnup_data_builder = new BurnupDataBuilder(
            $this->logger,
            $this->burnup_cache_checker,
            $this->chart_configuration_value_retriever,
            $this->burnup_cache_dao,
            $this->burnup_calculator,
            $this->count_cache_dao,
            $this->count_calculator,
            $this->mode_checker,
            $planning_dao,
            $planning_factory
        );
    }

    public function testItBuildsBurnupData(): void
    {
        $this->mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnFalse();

        $artifact = Mockery::mock(Artifact::class);
        $user     = UserTestBuilder::buildWithDefaults();

        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn($this->artifact_tracker);
        $artifact->shouldReceive('getTrackerId')->andReturn($this->artifact_tracker->getId());

        $time_period = \TimePeriodWithoutWeekEnd::buildFromDuration(1560760543, 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnFalse();

        $this->mockBurnupCacheDao();

        $this->count_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')->never();

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $efforts = $burnup_data->getEfforts();

        $this->assertCount(4, $efforts);
    }

    public function testItBuildsBurnupDataWithCountElementsInformation(): void
    {
        $this->mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnTrue();

        $artifact = Mockery::mock(Artifact::class);
        $user     = UserTestBuilder::buildWithDefaults();

        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn($this->artifact_tracker);
        $artifact->shouldReceive('getTrackerId')->andReturn($this->artifact_tracker->getId());

        $time_period = \TimePeriodWithoutWeekEnd::buildFromDuration(1560760543, 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnFalse();

        $this->mockBurnupCacheDao();
        $this->mockCountElementsCacheDao();

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $count_elements = $burnup_data->getEfforts();
        $this->assertCount(4, $count_elements);

        $count_elements = $burnup_data->getCountElements();
        $this->assertCount(4, $count_elements);

        $last_count_elements = end($count_elements);
        $this->assertInstanceOf(CountElementsInfo::class, $last_count_elements);
        $this->assertSame(4, $last_count_elements->getClosedElements());
        $this->assertSame(5, $last_count_elements->getTotalElements());
    }

    public function testItReturnsEmptyEffortsIfUnderCalculation(): void
    {
        $this->mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnFalse();

        $artifact = Mockery::mock(Artifact::class);
        $user     = UserTestBuilder::buildWithDefaults();

        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTrackerId')->andReturn($this->artifact_tracker->getId());

        $time_period = \TimePeriodWithoutWeekEnd::buildFromDuration(1560760543, 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnTrue();

        $this->burnup_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')->never();
        $this->count_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')->never();

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $this->assertEmpty($burnup_data->getEfforts());
    }

    public function testItBuildsBurnupDataWithCurrentDay(): void
    {
        $this->mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnTrue();

        $artifact = Mockery::mock(Artifact::class);
        $user     = UserTestBuilder::buildWithDefaults();

        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn($this->artifact_tracker);
        $artifact->shouldReceive('getTrackerId')->andReturn($this->artifact_tracker->getId());

        $start_date = new \DateTime();
        $start_date->setTime(0, 0, 0);

        $time_period = \TimePeriodWithoutWeekEnd::buildFromDuration($start_date->getTimestamp(), 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnFalse();

        $this->burnup_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')
            ->with(101, Mockery::any())
            ->andReturn([]);

        $this->count_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')
            ->with(101, Mockery::any())
            ->andReturn([]);

        $this->burnup_calculator->shouldReceive('getValue')
            ->with(101, Mockery::any(), [[$this->artifact_tracker->getId()]])
            ->andReturn(new BurnupEffort(5, 10));

        $this->count_calculator->shouldReceive('getValue')
            ->with(101, Mockery::any(), [[$this->artifact_tracker->getId()]])
            ->andReturn(new CountElementsInfo(3, 5));

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $efforts = $burnup_data->getEfforts();
        $this->assertCount(1, $efforts);

        $first_effort = array_values($efforts)[0];
        $this->assertInstanceOf(BurnupEffort::class, $first_effort);
        $this->assertSame(5, $first_effort->getTeamEffort());
        $this->assertSame(10, $first_effort->getTotalEffort());

        $count_elements = $burnup_data->getCountElements();
        $this->assertCount(1, $count_elements);

        $first_count_elements = array_values($count_elements)[0];
        $this->assertInstanceOf(CountElementsInfo::class, $first_count_elements);
        $this->assertSame(3, $first_count_elements->getClosedElements());
        $this->assertSame(5, $first_count_elements->getTotalElements());
    }

    private function mockBurnupCacheDao(): void
    {
        $this->burnup_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')
            ->with(101, Mockery::any())
            ->andReturn([
                [
                    'team_effort' => 0,
                    'total_effort' => 10,
                    'timestamp' => 1560729600,
                ],
                [
                    'team_effort' => 2,
                    'total_effort' => 10,
                    'timestamp' => 1560816000,
                ],
                [
                    'team_effort' => 6,
                    'total_effort' => 10,
                    'timestamp' => 1560902400,
                ],
                [
                    'team_effort' => 10,
                    'total_effort' => 10,
                    'timestamp' => 1560988800,
                ],
            ]);
    }

    private function mockCountElementsCacheDao(): void
    {
        $this->count_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')
            ->with(101, Mockery::any())
            ->andReturn([
                [
                    'closed_subelements' => 0,
                    'total_subelements' => 5,
                    'timestamp' => 1560729600,
                ],
                [
                    'closed_subelements' => 2,
                    'total_subelements' => 5,
                    'timestamp' => 1560816000,
                ],
                [
                    'closed_subelements' => 3,
                    'total_subelements' => 5,
                    'timestamp' => 1560902400,
                ],
                [
                    'closed_subelements' => 4,
                    'total_subelements' => 5,
                    'timestamp' => 1560988800,
                ],
            ]);
    }
}
