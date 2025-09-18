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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement;

use DateTime;
use ForgeConfig;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use Psr\Log\NullLogger;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsInfo;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurnupDataBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private BurnupDataBuilder $burnup_data_builder;
    private BurnupCacheChecker&MockObject $burnup_cache_checker;
    private ChartConfigurationValueRetriever&MockObject $chart_configuration_value_retriever;
    private BurnupCacheDao&MockObject $burnup_cache_dao;
    private BurnupCalculator&MockObject $burnup_calculator;
    private CountElementsCacheDao&MockObject $count_cache_dao;
    private CountElementsCalculator&MockObject $count_calculator;
    private CountElementsModeChecker&MockObject $mode_checker;
    private Tracker $artifact_tracker;

    #[Override]
    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');

        $this->burnup_cache_checker                = $this->createMock(BurnupCacheChecker::class);
        $this->chart_configuration_value_retriever = $this->createMock(ChartConfigurationValueRetriever::class);
        $this->burnup_cache_dao                    = $this->createMock(BurnupCacheDao::class);
        $this->burnup_calculator                   = $this->createMock(BurnupCalculator::class);
        $this->count_cache_dao                     = $this->createMock(CountElementsCacheDao::class);
        $this->count_calculator                    = $this->createMock(CountElementsCalculator::class);
        $this->mode_checker                        = $this->createMock(CountElementsModeChecker::class);
        $planning_dao                              = $this->createMock(PlanningDao::class);
        $planning_factory                          = $this->createMock(PlanningFactory::class);

        $this->artifact_tracker = TrackerTestBuilder::aTracker()->withId(10)->withProject(ProjectTestBuilder::aProject()->build())->build();

        $planning_dao->method('searchByMilestoneTrackerId')->willReturn(['id' => $this->artifact_tracker->getId()]);
        $planning_factory->method('getBacklogTrackersIds')->willReturn([[$this->artifact_tracker->getId()]]);

        $this->burnup_data_builder = new BurnupDataBuilder(
            new NullLogger(),
            $this->burnup_cache_checker,
            $this->chart_configuration_value_retriever,
            $this->burnup_cache_dao,
            $this->burnup_calculator,
            $this->count_cache_dao,
            $this->count_calculator,
            $this->mode_checker,
            $planning_dao,
            $planning_factory,
            new BurnupCacheDateRetriever(),
        );
    }

    public function testItBuildsBurnupData(): void
    {
        $this->mode_checker->method('burnupMustUseCountElementsMode')->willReturn(false);

        $artifact    = ArtifactTestBuilder::anArtifact(101)->inTracker($this->artifact_tracker)->build();
        $user        = UserTestBuilder::buildWithDefaults();
        $date_period = DatePeriodWithOpenDays::buildFromDuration(1560760543, 3);

        $this->chart_configuration_value_retriever->expects($this->once())->method('getDatePeriod')
            ->with($artifact, $user)
            ->willReturn($date_period);

        $this->burnup_cache_checker->expects($this->once())->method('isBurnupUnderCalculation')
            ->with($artifact, self::anything(), $user)
            ->willReturn(false);

        $this->mockBurnupCacheDao();

        $this->count_cache_dao->expects($this->never())->method('searchCachedDaysValuesByArtifactId');

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);
        $efforts     = $burnup_data->getEfforts();

        self::assertCount(4, $efforts);
    }

    public function testItBuildsBurnupDataWithCountElementsInformation(): void
    {
        $this->mode_checker->method('burnupMustUseCountElementsMode')->willReturn(true);

        $artifact    = ArtifactTestBuilder::anArtifact(101)->inTracker($this->artifact_tracker)->build();
        $user        = UserTestBuilder::buildWithDefaults();
        $date_period = DatePeriodWithOpenDays::buildFromDuration(1560760543, 3);

        $this->chart_configuration_value_retriever->expects($this->once())->method('getDatePeriod')
            ->with($artifact, $user)
            ->willReturn($date_period);

        $this->burnup_cache_checker->expects($this->once())->method('isBurnupUnderCalculation')
            ->with($artifact, self::anything(), $user)
            ->willReturn(false);

        $this->mockBurnupCacheDao();
        $this->mockCountElementsCacheDao();

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $count_elements = $burnup_data->getEfforts();
        self::assertCount(4, $count_elements);

        $count_elements = $burnup_data->getCountElements();
        self::assertCount(4, $count_elements);

        $last_count_elements = end($count_elements);
        self::assertInstanceOf(CountElementsInfo::class, $last_count_elements);
        self::assertSame(4, $last_count_elements->getClosedElements());
        self::assertSame(5, $last_count_elements->getTotalElements());
    }

    public function testItReturnsEmptyEffortsIfUnderCalculation(): void
    {
        $this->mode_checker->method('burnupMustUseCountElementsMode')->willReturn(false);

        $artifact    = ArtifactTestBuilder::anArtifact(101)->inTracker($this->artifact_tracker)->build();
        $user        = UserTestBuilder::buildWithDefaults();
        $date_period = DatePeriodWithOpenDays::buildFromDuration(1560760543, 3);

        $this->chart_configuration_value_retriever->expects($this->once())->method('getDatePeriod')
            ->with($artifact, $user)
            ->willReturn($date_period);

        $this->burnup_cache_checker->expects($this->once())->method('isBurnupUnderCalculation')
            ->with($artifact, self::anything(), $user)
            ->willReturn(true);

        $this->burnup_cache_dao->expects($this->never())->method('searchCachedDaysValuesByArtifactId');
        $this->count_cache_dao->expects($this->never())->method('searchCachedDaysValuesByArtifactId');

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        self::assertEmpty($burnup_data->getEfforts());
    }

    public function testItBuildsBurnupDataWithCurrentDay(): void
    {
        $this->mode_checker->method('burnupMustUseCountElementsMode')->willReturn(true);

        $artifact    = ArtifactTestBuilder::anArtifact(101)->inTracker($this->artifact_tracker)->build();
        $user        = UserTestBuilder::buildWithDefaults();
        $start_date  = (new DateTime())->setTime(0, 0);
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date->getTimestamp(), 3);

        $this->chart_configuration_value_retriever->expects($this->once())->method('getDatePeriod')
            ->with($artifact, $user)
            ->willReturn($date_period);

        $this->burnup_cache_checker->expects($this->once())->method('isBurnupUnderCalculation')
            ->with($artifact, self::anything(), $user)
            ->willReturn(false);

        $this->burnup_cache_dao->method('searchCachedDaysValuesByArtifactId')
            ->with(101, self::anything())
            ->willReturn([]);

        $this->count_cache_dao->method('searchCachedDaysValuesByArtifactId')
            ->with(101, self::anything())
            ->willReturn([]);

        $this->burnup_calculator->method('getValue')
            ->with(101, self::anything(), [[$this->artifact_tracker->getId()]])
            ->willReturn(new BurnupEffort(5, 10));

        $this->count_calculator->method('getValue')
            ->with(101, self::anything(), [[$this->artifact_tracker->getId()]])
            ->willReturn(new CountElementsInfo(3, 5));

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $efforts = $burnup_data->getEfforts();
        self::assertCount(1, $efforts);

        $first_effort = array_values($efforts)[0];
        self::assertInstanceOf(BurnupEffort::class, $first_effort);
        self::assertSame(5.0, $first_effort->getTeamEffort());
        self::assertSame(10.0, $first_effort->getTotalEffort());

        $count_elements = $burnup_data->getCountElements();
        self::assertCount(1, $count_elements);

        $first_count_elements = array_values($count_elements)[0];
        self::assertInstanceOf(CountElementsInfo::class, $first_count_elements);
        self::assertSame(3, $first_count_elements->getClosedElements());
        self::assertSame(5, $first_count_elements->getTotalElements());
    }

    private function mockBurnupCacheDao(): void
    {
        $this->burnup_cache_dao->method('searchCachedDaysValuesByArtifactId')
            ->with(101, self::anything())
            ->willReturn([
                [
                    'team_effort'  => 0,
                    'total_effort' => 10,
                    'timestamp'    => 1560729600,
                ],
                [
                    'team_effort'  => 2,
                    'total_effort' => 10,
                    'timestamp'    => 1560816000,
                ],
                [
                    'team_effort'  => 6,
                    'total_effort' => 10,
                    'timestamp'    => 1560902400,
                ],
                [
                    'team_effort'  => 10,
                    'total_effort' => 10,
                    'timestamp'    => 1560988800,
                ],
            ]);
    }

    private function mockCountElementsCacheDao(): void
    {
        $this->count_cache_dao->method('searchCachedDaysValuesByArtifactId')
            ->with(101, self::anything())
            ->willReturn([
                [
                    'closed_subelements' => 0,
                    'total_subelements'  => 5,
                    'timestamp'          => 1560729600,
                ],
                [
                    'closed_subelements' => 2,
                    'total_subelements'  => 5,
                    'timestamp'          => 1560816000,
                ],
                [
                    'closed_subelements' => 3,
                    'total_subelements'  => 5,
                    'timestamp'          => 1560902400,
                ],
                [
                    'closed_subelements' => 4,
                    'total_subelements'  => 5,
                    'timestamp'          => 1560988800,
                ],
            ]);
    }
}
