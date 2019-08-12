<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use TimePeriodWithoutWeekEnd;
use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class ChartCachedDaysComparatorTest extends TuleapTestCase
{
    public function itVerifiesCacheIsCompleteForChartWhenCacheDaysAreTheSameThanTimePeriodDays()
    {
        $number_of_cached_days = 6;
        $start_date            = mktime(0, 0, 0, 20, 12, 2016);
        $duration              = 5;

        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $cache_days_comparator = new ChartCachedDaysComparator(mock('Logger'));
        $this->assertTrue($cache_days_comparator->isNumberOfCachedDaysExpected($time_period, $number_of_cached_days));
    }

    public function itVerifiesCacheIsCompleteForChartWhenCacheDaysAreNotTheSameThanTimePeriodDays()
    {
        $number_of_cached_days = 6;
        $start_date            = mktime(0, 0, 0, 20, 12, 2016);
        $duration              = 15;

        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $cache_days_comparator = new ChartCachedDaysComparator(mock('Logger'));
        $this->assertFalse($cache_days_comparator->isNumberOfCachedDaysExpected($time_period, $number_of_cached_days));
    }
}
