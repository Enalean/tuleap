<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurnupCacheCheckerTest extends TestCase
{
    private BurnupCacheGenerator&MockObject $cache_generator;
    private PFUser $user;
    private Artifact $artifact;
    /**
     * @var int[]
     */
    private array $expected_days;
    private BurnupCacheChecker $burnup_cache_Checker;
    private ChartConfigurationValueChecker&MockObject $chart_value_checker;
    private BurnupCacheDao&MockObject $burnup_cache_dao;
    private CountElementsCacheDao&MockObject $count_elements_cache_dao;
    private CountElementsModeChecker&MockObject $count_elements_mode_checker;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->cache_generator             = $this->createMock(BurnupCacheGenerator::class);
        $this->chart_value_checker         = $this->createMock(ChartConfigurationValueChecker::class);
        $this->burnup_cache_dao            = $this->createMock(BurnupCacheDao::class);
        $this->count_elements_cache_dao    = $this->createMock(CountElementsCacheDao::class);
        $this->count_elements_mode_checker = $this->createMock(CountElementsModeChecker::class);
        $this->logger                      = new TestLogger();
        $this->burnup_cache_Checker        = new BurnupCacheChecker(
            $this->cache_generator,
            $this->chart_value_checker,
            $this->burnup_cache_dao,
            $this->count_elements_cache_dao,
            new ChartCachedDaysComparator(new NullLogger()),
            $this->count_elements_mode_checker,
            $this->logger
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(101)
            ->inTracker(
                TrackerTestBuilder::aTracker()
                    ->withProject(ProjectTestBuilder::aProject()->build())
                    ->build()
            )
            ->build();

        $this->expected_days = [
            (new DateTime('2024-01-12 23:59'))->getTimestamp(),
            (new DateTime('2024-01-13 23:59'))->getTimestamp(),
            (new DateTime('2024-01-14 23:59'))->getTimestamp(),
            (new DateTime('2024-01-15 23:59'))->getTimestamp(),
            (new DateTime('2024-01-16 23:59'))->getTimestamp(),
        ];

        $this->user = UserTestBuilder::buildWithId(101);
    }

    public function testItReturnsFalseWhenStartDateFieldIsNotReadable(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(false);
        $this->cache_generator->method('isCacheBurnupAlreadyAsked');
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-01-12'), 5);

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
    }

    public function testItReturnsTrueWhenBurnupIsAlreadyUnderCalculation(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);
        $this->count_elements_mode_checker->method('burnupMustUseCountElementsMode')->willReturn(false);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(true);
        $this->cache_generator->method('forceBurnupCacheGeneration');
        $this->burnup_cache_dao->method('getCachedDaysTimestamps')->willReturn([]);
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-01-12'), 5);

        self::assertTrue($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
    }

    public function testItReturnsTrueAndSendAnEventWhenCacheIsIncompleteForBurnupInEffortMode(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);
        $this->count_elements_mode_checker->method('burnupMustUseCountElementsMode')->willReturn(false);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(false);
        $this->burnup_cache_dao->method('getCachedDaysTimestamps')->willReturn([]);

        $this->cache_generator->expects($this->once())->method('forceBurnupCacheGeneration');
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-01-12'), 5);

        self::assertTrue($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
    }

    public function testItReturnsFalseWhenBurnupInEffortModeHasNoNeedToBeComputed(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);
        $this->count_elements_mode_checker->method('burnupMustUseCountElementsMode')->willReturn(false);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(false);
        $this->burnup_cache_dao->method('getCachedDaysTimestamps')->willReturn($this->expected_days);

        $this->cache_generator->expects(self::never())->method('forceBurnupCacheGeneration')->with($this->artifact->getId());
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-01-12'), 5);

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
    }

    public function testItReturnsFalseWhenBurnupInCountElementsModeHasNoNeedToBeComputed(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);
        $this->count_elements_mode_checker->method('burnupMustUseCountElementsMode')->willReturn(true);
        $this->count_elements_cache_dao->expects($this->once())->method('getCachedDaysTimestamps')->willReturn($this->expected_days);
        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->willReturn(false);
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-01-12'), 5);

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
    }

    public function testItReturnsTrueAndSendAnEventWhenCacheIsIncompleteForBurnupInCountElementsMode(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);
        $this->count_elements_mode_checker->method('burnupMustUseCountElementsMode')->willReturn(true);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(false);
        $this->count_elements_cache_dao->method('getCachedDaysTimestamps')->willReturn([]);

        $this->cache_generator->expects($this->once())->method('forceBurnupCacheGeneration');
        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-01-12'), 5);

        self::assertTrue($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
    }

    public function testItReturnsFalseWhenStartDateIsInTheFuture(): void
    {
        $this->count_elements_cache_dao->method('getCachedDaysTimestamps')->willReturn($this->expected_days);

        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('tomorrow'), 5);

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
        self::assertTrue($this->logger->hasDebug('Cache is always valid when start date is in future'));
    }

    public function testItReturnsFalseWhenDurationIsZero(): void
    {
        $this->count_elements_cache_dao->method('getCachedDaysTimestamps')->willReturn($this->expected_days);

        $date_period = DatePeriodWithOpenDays::buildFromDuration(strtotime('2024-11-01'), 0);

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->expected_days, $this->user, $date_period));
        self::assertTrue($this->logger->hasDebug('Cache is always valid when burnup has no duration'));
    }
}
