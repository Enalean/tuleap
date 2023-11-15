<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

use DateTime;
use Tuleap\Date\DatePeriodWithoutWeekEnd;

class BurndownCacheDateRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var BurndownCacheDateRetriever
     */
    private $burndown_cache_retriever;

    protected function setUp(): void
    {
        $this->burndown_cache_retriever = new BurndownCacheDateRetriever();
    }

    public function testItAssertThatNumberOfDaysAreCorrectWhenBurndownIsComputedInPast(): void
    {
        $start_date           = mktime(23, 59, 59, 10, 1, 2016);
        $octobre_month_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date, 20);

        $today      = mktime(0, 0, 0, 11, 8, 2016);
        $today_time = new DateTime();
        $today_time->setTimestamp($today);

        $period = $this->burndown_cache_retriever->getWorkedDaysToCacheForPeriod($octobre_month_period, $today_time);

        $expected_period = [
            mktime(23, 59, 59, 10, 3, 2016),
            mktime(23, 59, 59, 10, 4, 2016),
            mktime(23, 59, 59, 10, 5, 2016),
            mktime(23, 59, 59, 10, 6, 2016),
            mktime(23, 59, 59, 10, 7, 2016),
            mktime(23, 59, 59, 10, 10, 2016),
            mktime(23, 59, 59, 10, 11, 2016),
            mktime(23, 59, 59, 10, 12, 2016),
            mktime(23, 59, 59, 10, 13, 2016),
            mktime(23, 59, 59, 10, 14, 2016),
            mktime(23, 59, 59, 10, 17, 2016),
            mktime(23, 59, 59, 10, 18, 2016),
            mktime(23, 59, 59, 10, 19, 2016),
            mktime(23, 59, 59, 10, 20, 2016),
            mktime(23, 59, 59, 10, 21, 2016),
            mktime(23, 59, 59, 10, 24, 2016),
            mktime(23, 59, 59, 10, 25, 2016),
            mktime(23, 59, 59, 10, 26, 2016),
            mktime(23, 59, 59, 10, 27, 2016),
            mktime(23, 59, 59, 10, 28, 2016),
            mktime(23, 59, 59, 10, 31, 2016),
        ];

        $this->assertCount(21, $period);
        $this->assertEquals($expected_period, $period);
    }

    public function testItAssertThatNumberOfDaysAreCorrectWhenBurndownIsCurrent(): void
    {
        $start_date           = mktime(23, 59, 59, 10, 1, 2016);
        $octobre_month_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date, 21);

        $today      = mktime(0, 0, 0, 10, 15, 2016);
        $today_time = new DateTime();
        $today_time->setTimestamp($today);

        $period = $this->burndown_cache_retriever->getWorkedDaysToCacheForPeriod($octobre_month_period, $today_time);

        $expected_period = [
            mktime(23, 59, 59, 10, 3, 2016),
            mktime(23, 59, 59, 10, 4, 2016),
            mktime(23, 59, 59, 10, 5, 2016),
            mktime(23, 59, 59, 10, 6, 2016),
            mktime(23, 59, 59, 10, 7, 2016),
            mktime(23, 59, 59, 10, 10, 2016),
            mktime(23, 59, 59, 10, 11, 2016),
            mktime(23, 59, 59, 10, 12, 2016),
            mktime(23, 59, 59, 10, 13, 2016),
            mktime(23, 59, 59, 10, 14, 2016),
        ];

        $this->assertCount(10, $period);

        $this->assertEquals($expected_period, $period);
    }
}
