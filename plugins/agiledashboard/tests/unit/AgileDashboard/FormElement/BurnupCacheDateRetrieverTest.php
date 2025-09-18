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

namespace Tuleap\AgileDashboard\FormElement;

use Tuleap\Date\DatePeriodWithOpenDays;

require_once __DIR__ . '/../../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurnupCacheDateRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItGetsDaysToCacheWhenPeriodIsOngoing(): void
    {
        $start_date = \DateTime::createFromFormat('d-m-Y H:i', '18-12-2017 00:00');
        $yesterday  = \DateTime::createFromFormat('d-m-Y', '20-12-2017');
        $period     = DatePeriodWithOpenDays::buildFromDuration($start_date->getTimestamp(), 5);

        $date_cache_retriever = new BurnupCacheDateRetriever();
        $days_to_cache        = $date_cache_retriever->getWorkedDaysToCacheForPeriod($period, $yesterday);

        $expected_days = [
            \DateTime::createFromFormat('d-m-Y H:i:s', '18-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '19-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '20-12-2017 23:59:00')->getTimestamp(),
        ];

        self::assertSame($days_to_cache, $expected_days);
    }

    public function testItGetsDaysToCacheWhenPeriodHasEnded(): void
    {
        $start_date = \DateTime::createFromFormat('d-m-Y H:i', '18-12-2017 00:00');
        $yesterday  = \DateTime::createFromFormat('d-m-Y', '26-12-2017');
        $period     = DatePeriodWithOpenDays::buildFromDuration($start_date->getTimestamp(), 2);

        $date_cache_retriever = new BurnupCacheDateRetriever();
        $days_to_cache        = $date_cache_retriever->getWorkedDaysToCacheForPeriod($period, $yesterday);

        $expected_days = [
            \DateTime::createFromFormat('d-m-Y H:i:s', '18-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '19-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '20-12-2017 23:59:00')->getTimestamp(),
        ];

        self::assertSame($days_to_cache, $expected_days);
    }

    public function testItGetsDayToCacheWhenPeriodHasNotYetStarted(): void
    {
        $start_date = \DateTime::createFromFormat('d-m-Y H:i', '26-12-2017 00:00');
        $yesterday  = \DateTime::createFromFormat('d-m-Y', '20-12-2017');
        $period     = DatePeriodWithOpenDays::buildFromDuration($start_date->getTimestamp(), 4);

        $date_cache_retriever = new BurnupCacheDateRetriever();
        $days_to_cache        = $date_cache_retriever->getWorkedDaysToCacheForPeriod($period, $yesterday);

        $expected_days = [];

        self::assertSame($days_to_cache, $expected_days);
    }

    public function testItGetsDayToCacheWhenPeriodHasAWeekEnd(): void
    {
        $start_date = \DateTime::createFromFormat('d-m-Y H:i', '21-12-2017 00:00');
        $yesterday  = \DateTime::createFromFormat('d-m-Y', '27-12-2017');
        $period     = DatePeriodWithOpenDays::buildFromDuration($start_date->getTimestamp(), 5);

        $date_cache_retriever = new BurnupCacheDateRetriever();
        $days_to_cache        = $date_cache_retriever->getWorkedDaysToCacheForPeriod($period, $yesterday);

        $expected_days = [
            \DateTime::createFromFormat('d-m-Y H:i:s', '21-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '22-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '25-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '26-12-2017 23:59:00')->getTimestamp(),
            \DateTime::createFromFormat('d-m-Y H:i:s', '27-12-2017 23:59:00')->getTimestamp(),
        ];

        self::assertSame($days_to_cache, $expected_days);
    }
}
