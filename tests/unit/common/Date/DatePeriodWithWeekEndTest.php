<?php
/**
 * Copyright Enalean (c) 2012 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Date;

use DateTime;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DatePeriodWithWeekEndTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private DatePeriodWithWeekEnd $date_period;
    private int $day_timestamp;
    private int $following_day_timestamp;

    protected function setUp(): void
    {
        $start_date        = mktime(0, 0, 0, 7, 4, 2012);
        $this->date_period = new DatePeriodWithWeekEnd($start_date, 3);

        $week_day                      = new DateTime('2016-02-01');
        $this->day_timestamp           = $week_day->getTimestamp();
        $following_day                 = new DateTime('2016-02-02');
        $this->following_day_timestamp = $following_day->getTimestamp();
    }

    public function testItProvidesAListOfTheDayOffsetsInTheDatePeriod(): void
    {
        self::assertSame([0, 1, 2, 3], $this->date_period->getDayOffsets());
    }

    public function testItProvidesTheEndDate(): void
    {
        self::assertSame('Sat 07', date('D d', $this->date_period->getEndDate()));
    }

    public function testItProcessesNegativeDuration(): void
    {
        $date_period = new DatePeriodWithWeekEnd($this->day_timestamp, -2);
        self::assertSame($this->day_timestamp, $date_period->getEndDate());
    }

    public function testItProcessesNullDuration(): void
    {
        $date_period = new DatePeriodWithWeekEnd($this->day_timestamp, 0);
        self::assertSame($this->day_timestamp, $date_period->getEndDate());
    }

    public function testItProcessesPositiveDuration(): void
    {
        $date_period = new DatePeriodWithWeekEnd($this->day_timestamp, 1);
        self::assertSame($this->following_day_timestamp, $date_period->getEndDate());
    }

    public function testItProcessesFloatDuration(): void
    {
        $date_period = new DatePeriodWithWeekEnd($this->day_timestamp, 0.2);
        self::assertSame($this->following_day_timestamp, $date_period->getEndDate());
    }

    public function testItProcessesFloatDurationAsStringValue(): void
    {
        $date_period = new DatePeriodWithWeekEnd($this->day_timestamp, '0.2');
        self::assertSame($this->following_day_timestamp, $date_period->getEndDate());
    }

    public function testItProcessesDurationAsStringValue(): void
    {
        $date_period = new DatePeriodWithWeekEnd($this->day_timestamp, '1');
        self::assertSame($this->following_day_timestamp, $date_period->getEndDate());
    }
}
