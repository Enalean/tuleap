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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TimePeriodWithoutWeekEnd;

class TimePeriodWithoutWeekEndTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $week_day_timestamp;

    /**
     * @var int
     */
    private $following_week_day_timestamp;

    protected function setUp(): void
    {
        $week_day                           = new DateTime('2016-02-01');
        $this->week_day_timestamp           = $week_day->getTimestamp();
        $following_week_day                 = new DateTime('2016-02-02');
        $this->following_week_day_timestamp = $following_week_day->getTimestamp();
    }

    public function testItProvidesAListOfDaysWhileExcludingWeekends()
    {
        $start_date  = mktime(0, 0, 0, 7, 4, 2012);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, 4);

        $this->assertSame(
            ['Wed 04', 'Thu 05', 'Fri 06', 'Mon 09', 'Tue 10'],
            $time_period->getHumanReadableDates()
        );
    }

    public function testItProvidesTheEndDate()
    {
        $start_date  = mktime(0, 0, 0, 7, 4, 2012);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, 4);

        $this->assertSame('Tue 10', date('D d', $time_period->getEndDate()));
    }

    public function testItProvidesTheCorrectNumberOfDayWhenLastDateOfBurndownIsBeforeToday()
    {
        $start_date  = mktime(23, 59, 59, 11, 5, 2016);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, 4);

        $today = mktime(23, 59, 59, 11, 12, 2016);
        $timestamps = $time_period->getCountDayUntilDate($today);

        $this->assertSame(5, $timestamps);
    }

    public function testItProvidesTheCorrectNumberOfDayWhenLastDateOfBurndownIsAfterToday()
    {
        $start_date  = mktime(23, 59, 59, 11, 5, 2016);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, 4);

        $today = mktime(23, 59, 59, 11, 8, 2016);
        $timestamps = $time_period->getCountDayUntilDate($today);

        $this->assertSame(1, $timestamps);
    }

    public function testItDoesNotCountTheStartDate()
    {
        $start_date  = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 15))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 1, 31, 2014)));

        $this->assertSame(0, $time_period->getNumberOfDaysSinceStart());
    }

    public function testItCountsTheNextDayAsOneDay()
    {
        $start_date  = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 15))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 4, 2014)));

        $this->assertSame(1, $time_period->getNumberOfDaysSinceStart());
    }

    public function testItCountsAWeekAsFiveDays()
    {
        $start_date  = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 15))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));

        $this->assertSame(5, $time_period->getNumberOfDaysSinceStart());
    }

    public function testItCountsAWeekendAsNothing()
    {
        $start_date  = mktime(0, 0, 0, 2, 7, 2014);
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 15))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));

        $this->assertSame(1, $time_period->getNumberOfDaysSinceStart());
    }

    public function testItExcludesAllTheWeekends()
    {
        $start_date  = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 15))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2014)));

        $this->assertSame(19, $time_period->getNumberOfDaysSinceStart());
    }

    public function testItIgnoresFutureStartDates()
    {
        $start_date  = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 15))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2013)));

        $this->assertSame(0, $time_period->getNumberOfDaysSinceStart());
    }

    public function testItAcceptsToday()
    {
        $start_date = mktime(0, 0, 0, 2, 6, 2014);
        $duration   = 10;

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, $duration))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 6, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function testItAcceptsTodayIfZeroDuration()
    {
        $start_date = mktime(0, 0, 0, 2, 6, 2014);
        $duration   = 0;

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, $duration))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 6, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function testItRefusesTomorrow()
    {
        $start_date = mktime(0, 0, 0, 2, 7, 2014);
        $duration   = 10;

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, $duration))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 6, 2014)));

        $this->assertFalse($time_period->isTodayWithinTimePeriod());
    }

    public function testItWorksInStandardCase()
    {
        $start_date = mktime(0, 0, 0, 2, 7, 2014);
        $duration   = 10;

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, $duration))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 13, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function testItAcceptsLastDayOfPeriod()
    {
        $start_date = mktime(0, 0, 0, 2, 5, 2014);
        $duration   = 10;

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, $duration))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 19, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function testItRefusesTheDayAfterTheLastDayOfPeriod()
    {
        $start_date = mktime(0, 0, 0, 2, 5, 2014);
        $duration   = 9;

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, $duration))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 19, 2014)));

        $this->assertFalse($time_period->isTodayWithinTimePeriod());
    }

    public function testItLetsTheFullDurationAtStart()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 3, 2014)));

        $this->assertSame(10, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItLetsDurationMinusOneTheDayAfter()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 4, 2014)));

        $this->assertSame(9, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItLetsFiveDaysDuringTheWeekEndAtTheMiddleOfTheTwoSprints()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 8, 2014)));

        $this->assertSame(5, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItLetsFiveDaysAtTheBeginningOfSecondWeek()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));

        $this->assertSame(5, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItLetsOneDayOnTheLastDayOfSprint()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 14, 2014)));

        $this->assertSame(1, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItIsZeroDuringTheWeekEndJustBeforeTheEndDate()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 15, 2014)));

        $this->assertSame(0, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItIsZeroWhenTheTimeHasCome()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 17, 2014)));

        $this->assertSame(0, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItsMinus4TheFridayAfterTheEndOfTheSprint()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 21, 2014)));

        $this->assertSame(-4, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItsMinus5TheWeekEndAfterTheEndOfTheSprint()
    {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 22, 2014)));

        $this->assertSame(-5, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItAddsTheMissingDayWhenStartDateIsInTheFuture()
    {
        $start_date = mktime(0, 0, 0, 2, 4, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 3, 2014)));

        $this->assertSame(11, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItAddsTheMissingDayWithoutWeekEndWhenStartDateIsInTheFuture()
    {
        $start_date = mktime(0, 0, 0, 2, 4, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 10))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 1, 31, 2014)));

        $this->assertSame(12, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItContinuesWhenTheEndDateIsOver()
    {
        $start_date = mktime(0, 0, 0, 1, 14, 2014);

        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class, array($start_date, 14))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $time_period->shouldReceive('getTodayDate')->andReturn(date('Y-m-d', mktime(0, 0, 0, 2, 18, 2014)));

        $this->assertSame(-11, $time_period->getNumberOfDaysUntilEnd());
    }

    public function testItProcessesNegativeDuration()
    {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, -2);

        $this->assertSame($this->week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesNullDuration()
    {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, 0);

        $this->assertSame($this->week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesNullDurationWithAWeekEnd()
    {
        $week_end_day  = new DateTime('2016-02-06');
        $next_week_day = new DateTime('2016-02-08');
        $time_period   = new TimePeriodWithoutWeekEnd($week_end_day->getTimestamp(), 0);

        $this->assertSame($next_week_day->getTimestamp(), $time_period->getEndDate());
    }

    public function testItProcessesPositiveDuration()
    {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, 1);

        $this->assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesFloatDuration()
    {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, 0.21);

        $this->assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesPositiveDurationAsStringValue()
    {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, "1");

        $this->assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesFloatDurationAsStringValue()
    {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, "0.21");

        $this->assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }
}
