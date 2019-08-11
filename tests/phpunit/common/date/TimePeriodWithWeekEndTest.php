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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TimePeriodWithWeekEnd;

class TimePeriodWithWeekEndTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TimePeriodWithWeekEnd
     */
    private $time_period;

    /**
     * @var int
     */
    private $day_timestamp;

    /**
     * @var int
     */
    private $following_day_timestamp;

    protected function setUp(): void
    {
        $start_date        = mktime(0, 0, 0, 7, 4, 2012);
        $this->time_period = new TimePeriodWithWeekEnd($start_date, 3);

        $week_day                      = new DateTime('2016-02-01');
        $this->day_timestamp           = $week_day->getTimestamp();
        $following_day                 = new DateTime('2016-02-02');
        $this->following_day_timestamp = $following_day->getTimestamp();
    }

    public function testItProvidesAListOfTheDayOffsetsInTheTimePeriod()
    {
        $this->assertSame([0, 1, 2, 3], $this->time_period->getDayOffsets());
    }

    public function testItProvidesTheEndDate()
    {
        $this->assertSame('Sat 07', date('D d', $this->time_period->getEndDate()));
    }

    public function testItProcessesNegativeDuration()
    {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, -2);
        $this->assertSame($this->day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesNullDuration()
    {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, 0);
        $this->assertSame($this->day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesPositiveDuration()
    {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, 1);
        $this->assertSame($this->following_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesFloatDuration()
    {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, 0.2);
        $this->assertSame($this->following_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesFloatDurationAsStringValue()
    {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, "0.2");
        $this->assertSame($this->following_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesDurationAsStringValue()
    {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, "1");
        $this->assertSame($this->following_day_timestamp, $time_period->getEndDate());
    }
}
