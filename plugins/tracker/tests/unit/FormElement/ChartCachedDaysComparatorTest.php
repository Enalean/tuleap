<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use DateTime;
use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\Vec\shuffle;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChartCachedDaysComparatorTest extends TestCase
{
    public function testItVerifiesCacheIsCompleteForChartWhenCacheDaysAreTheSameThanDatePeriodDays(): void
    {
        $expected_days = [
            (new DateTime('2016-12-20 23:59'))->getTimestamp(),
            (new DateTime('2016-12-21 23:59'))->getTimestamp(),
            (new DateTime('2016-12-22 23:59'))->getTimestamp(),
            (new DateTime('2016-12-23 23:59'))->getTimestamp(),
            (new DateTime('2016-12-24 23:59'))->getTimestamp(),
            (new DateTime('2016-12-25 23:59'))->getTimestamp(),
        ];

        $cache_days_comparator = new ChartCachedDaysComparator(new NullLogger());
        $this->assertTrue($cache_days_comparator->areCachedDaysCorrect($expected_days, $expected_days));
    }

    public function testItVerifiesCacheIsCompleteForChartWhenCacheDaysAreNotTheSameThanDatePeriodDays(): void
    {
        $cached_days = [
            (new DateTime('2016-12-19 23:59'))->getTimestamp(),
            (new DateTime('2016-12-20 23:59'))->getTimestamp(),
        ];

        $expected_days = [
            (new DateTime('2016-12-20 23:59'))->getTimestamp(),
            (new DateTime('2016-12-21 23:59'))->getTimestamp(),
        ];

        $cache_days_comparator = new ChartCachedDaysComparator(new NullLogger());
        $this->assertFalse($cache_days_comparator->areCachedDaysCorrect($expected_days, $cached_days));
    }

    public function testItReturnsFalseIfCacheHasOneMoreDayThanExpected(): void
    {
        $expected_days = [
            (new DateTime('2016-12-19 23:59'))->getTimestamp(),
        ];

        $cached_days = [
            ...$expected_days,
            (new DateTime('2016-12-20 23:59'))->getTimestamp(),
        ];

        $cache_days_comparator = new ChartCachedDaysComparator(new NullLogger());
        $this->assertFalse($cache_days_comparator->areCachedDaysCorrect($expected_days, $cached_days));
    }

    public function testItReturnsFalseIfCacheHasOneFewerDayThanExpected(): void
    {
        $cached_days = [
            (new DateTime('2016-12-19 23:59'))->getTimestamp(),
        ];

        $expected_days = [
            ...$cached_days,
            (new DateTime('2016-12-20 23:59'))->getTimestamp(),
        ];

        $cache_days_comparator = new ChartCachedDaysComparator(new NullLogger());
        $this->assertFalse($cache_days_comparator->areCachedDaysCorrect($expected_days, $cached_days));
    }

    public function testItReturnsFalseIfTimestampsAreNotExactlyTheSame(): void
    {
        $expected_days = [
            (new DateTime('2016-12-19 23:59:00'))->getTimestamp(),
            (new DateTime('2016-12-20 23:59:00'))->getTimestamp(),
        ];

        $cached_days = [
            (new DateTime('2016-12-19 23:59:01'))->getTimestamp(),
            (new DateTime('2016-12-20 23:59:01'))->getTimestamp(),
        ];

        $cache_days_comparator = new ChartCachedDaysComparator(new NullLogger());
        $this->assertFalse($cache_days_comparator->areCachedDaysCorrect($expected_days, $cached_days));
    }

    public function testItReturnsTrueIfCacheIsValidNoMatterTheOrder(): void
    {
        $expected_days = [
            (new DateTime('2016-12-20 23:59'))->getTimestamp(),
            (new DateTime('2016-12-21 23:59'))->getTimestamp(),
            (new DateTime('2016-12-22 23:59'))->getTimestamp(),
            (new DateTime('2016-12-23 23:59'))->getTimestamp(),
            (new DateTime('2016-12-24 23:59'))->getTimestamp(),
            (new DateTime('2016-12-25 23:59'))->getTimestamp(),
        ];

        $cache_days_comparator = new ChartCachedDaysComparator(new NullLogger());
        $this->assertTrue($cache_days_comparator->areCachedDaysCorrect(shuffle($expected_days), shuffle($expected_days)));
    }
}
