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
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

require_once __DIR__ . '/../../bootstrap.php';

final class BurnupCacheCheckerTest extends TestCase
{
    private ChartCachedDaysComparator&MockObject $cache_days_comparator;
    private BurnupCacheGenerator&MockObject $cache_generator;
    private PFUser $user;
    private Artifact $artifact;
    private DatePeriodWithoutWeekEnd $date_period;
    private BurnupCacheChecker $burnup_cache_Checker;
    private ChartConfigurationValueChecker&MockObject $chart_value_checker;
    private BurnupCacheDao&MockObject $burnup_cache_dao;

    protected function setUp(): void
    {
        $this->cache_generator       = $this->createMock(BurnupCacheGenerator::class);
        $this->chart_value_checker   = $this->createMock(ChartConfigurationValueChecker::class);
        $this->burnup_cache_dao      = $this->createMock(BurnupCacheDao::class);
        $this->cache_days_comparator = $this->createMock(ChartCachedDaysComparator::class);
        $this->burnup_cache_Checker  = new BurnupCacheChecker(
            $this->cache_generator,
            $this->chart_value_checker,
            $this->burnup_cache_dao,
            $this->cache_days_comparator
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();

        $this->date_period = DatePeriodWithoutWeekEnd::buildFromDuration((new DateTime())->getTimestamp(), 10);

        $this->user = UserTestBuilder::buildWithId(101);
    }

    public function testItReturnsFalseWhenStartDateFieldIsNotReadable(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(false);
        $this->cache_generator->method('isCacheBurnupAlreadyAsked');

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user));
    }

    public function testItReturnsTrueWhenBurnupIsAlreadyUnderCalculation(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(true);
        $this->cache_generator->method('forceBurnupCacheGeneration');
        $this->burnup_cache_dao->method('getNumberOfCachedDays')->willReturn(['cached_days' => 1]);
        $this->cache_days_comparator->method('isNumberOfCachedDaysExpected')
            ->with($this->date_period, 1)
            ->willReturn(false);

        self::assertTrue($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user));
    }

    public function testItReturnsTrueAndSendAnEventWhenCacheIsIncompleteForBurnup(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(false);
        $this->burnup_cache_dao->method('getNumberOfCachedDays')->willReturn(['cached_days' => 1]);
        $this->cache_days_comparator->method('isNumberOfCachedDaysExpected')
            ->with($this->date_period, 1)
            ->willReturn(false);

        $this->cache_generator->expects(self::once())->method('forceBurnupCacheGeneration');

        self::assertTrue($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user));
    }

    public function testItReturnsFalseWhenBurnupHasNoNeedToBeComputed(): void
    {
        $this->chart_value_checker->method('hasStartDate')->willReturn(true);

        $this->cache_generator->method('isCacheBurnupAlreadyAsked')->with($this->artifact)->willReturn(false);
        $this->burnup_cache_dao->method('getNumberOfCachedDays')->willReturn(['cached_days' => 1]);
        $this->cache_days_comparator->method('isNumberOfCachedDaysExpected')
            ->with($this->date_period, 1)
            ->willReturn(true);

        $this->cache_generator->expects(self::never())->method('forceBurnupCacheGeneration')->with($this->artifact->getId());

        self::assertFalse($this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user));
    }
}
