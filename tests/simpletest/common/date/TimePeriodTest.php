<?php
/**
 * Copyright (c) Enalean, 2012 - 2015. All Rights Reserved.
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
require_once 'common/date/TimePeriodWithWeekEnd.class.php';
require_once 'common/date/TimePeriodWithoutWeekEnd.class.php';

class TimePeriodWithWeekEndTest extends TuleapTestCase {

    public function setUp() {
        $start_date        = mktime(0, 0, 0, 7, 4, 2012);
        $this->time_period = new TimePeriodWithWeekEnd($start_date, 3);
    }

    public function itComputesDateBasedOnStartDate() {
        $this->assertEqual(
            $this->time_period->getHumanReadableDates(),
            array('Wed 04', 'Thu 05', 'Fri 06', 'Sat 07')
        );
    }

    public function itProvidesAListOfTheDayOffsetsInTheTimePeriod() {
        $this->assertEqual($this->time_period->getDayOffsets(), array(0, 1, 2, 3));
    }

    public function itProvidesTheEndDate() {
        $this->assertEqual(date('D d', $this->time_period->getEndDate()), 'Sat 07');
    }
}

class TimePeriodWithoutWeekEndTest extends TuleapTestCase {

    public function setUp() {
        $start_date        = mktime(0, 0, 0, 7, 4, 2012);
        $this->time_period = new TimePeriodWithoutWeekEnd($start_date, 4);
    }

    public function itProvidesAListOfDaysWhileExcludingWeekends() {
        $this->assertEqual(
            $this->time_period->getHumanReadableDates(),
            array('Wed 04', 'Thu 05', 'Fri 06', 'Mon 09', 'Tue 10')
        );
    }

    public function itProvidesTheEndDate() {
        $this->assertEqual(date('D d', $this->time_period->getEndDate()), 'Tue 10');
    }

    public function itProvidesTheCorrectNumberOfDayWhenLastDateOfBurndwonIsBeforeToday()
    {
        $start_date  = mktime(23, 59, 59, 11, 5, 2016);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, 4);

        $today = mktime(23, 59, 59, 11, 12, 2016);
        $timestamps = $time_period->getCountDayUntilDate($today);

        $this->assertEqual($timestamps, 5);
    }

    public function itProvidesTheCorrectNumberOfDayWhenLastDateOfBurndwonIsAfterToday()
    {
        $start_date  = mktime(23, 59, 59, 11, 5, 2016);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, 4);

        $today = mktime(23, 59, 59, 11, 8, 2016);
        $timestamps = $time_period->getCountDayUntilDate($today);

        $this->assertEqual($timestamps, 1);
    }
}

class TimePeriodWithoutWeekEnd_getNumberOfDaysSinceStartTest extends TuleapTestCase {

    public function itDoesNotCountTheStartDate() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 1, 31, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysSinceStart(), 0);
    }

    public function itCountsTheNextDayAsOneDay() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 4, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysSinceStart(), 1);
    }

    public function itCountsAWeekAsFiveDays() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysSinceStart(), 5);
    }

    public function itCountsAWeekendAsNothing() {
        $start_date = mktime(0, 0, 0, 2, 7, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysSinceStart(), 1);
    }

    public function itExcludesAllTheWeekends() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysSinceStart(), 19);
    }

    public function itIgnoresFutureStartDates() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2013)));
        $this->assertEqual($time_period->getNumberOfDaysSinceStart(), 0);
    }
}

class TimePeriodWithoutWeekEnd_isTodayWithinTimePeriodTest extends TuleapTestCase {

    public function itAcceptsToday() {
        $start_date = mktime(0, 0, 0, 2, 6, 2014);
        $duration   = 10;

        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, $duration));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 6, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function itAcceptsTodayIfZeroDuration() {
        $start_date = mktime(0, 0, 0, 2, 6, 2014);
        $duration   = 0;

        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, $duration));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 6, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function itRefusesTomorrow() {
        $start_date = mktime(0, 0, 0, 2, 7, 2014);
        $duration   = 10;

        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, $duration));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 6, 2014)));

        $this->assertFalse($time_period->isTodayWithinTimePeriod());
    }

    public function itWorksInStandardCase() {
        $start_date = mktime(0, 0, 0, 2, 7, 2014);
        $duration   = 10;

        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, $duration));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 13, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function itAcceptsLastDayOfPeriod() {
        $start_date = mktime(0, 0, 0, 2, 5, 2014);
        $duration   = 10;

        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, $duration));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 19, 2014)));

        $this->assertTrue($time_period->isTodayWithinTimePeriod());
    }

    public function itRefusesTheDayAfterTheLastDayOfPeriod() {
        $start_date = mktime(0, 0, 0, 2, 5, 2014);
        $duration   = 9;

        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, $duration));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 19, 2014)));

        $this->assertFalse($time_period->isTodayWithinTimePeriod());
    }
}

/**
 * Given the following sprint:
 * Start date: 2 feb 2014
 * Duration: 10 days
 * Fri 31 jan; 11 days remaining
 * Mon 2 feb: 10 days remaining
 * Tue 3 feb: 9 days remaining
 * ...
 * Fri 7 feb: 6 days remaining
 * Sat/Sun 8/9 feb: 5 days remaining (we consider the week-end as next monday)
 * Mon 10 feb: 5 days remaining
 * Fri 14 feb: 1 day remaining
 * Sat/Sun 15/16: O day remaining
 * Tue 18: -1 day remaining
 */
class TimePeriodWithoutWeekEnd_getNumberOfRemainingDaysTest extends TuleapTestCase {

    public function itLetTheFullDurationAtStart() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 3, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 10);
    }

    public function itLetDurationMinusOneTheDayAfter() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 4, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 9);
    }

    public function itLetFiveDaysDuringTheWeekEndAtTheMiddleOfTheTwoSprints() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 8, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 5);
    }

    public function itLetFiveDaysAtTheBeginningOfSecondWeek() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 5);
    }

    public function itLetOneDayOnTheLastDayOfSprint() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 14, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 1);
    }

    public function itIsZeroDuringTheWeekEndJustBeforeTheEndDate() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 15, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 0);
    }

    public function itIsZeroWhenTheTimeHasCome() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 17, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 0);
    }

    public function itsMinus4TheFridayAfterTheEndOfTheSprint() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 21, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), -4);
    }

    public function itsMinus5TheWeekEndAfterTheEndOfTheSprint() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 22, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), -5);
    }

    public function itAddsTheMissingDayWhenStartDateIsInTheFuture() {
        $start_date = mktime(0, 0, 0, 2, 4, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 3, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 11);
    }

    public function itAddsTheMissingDayWithoutWeekEndWhenStartDateIsInTheFuture() {
        $start_date = mktime(0, 0, 0, 2, 4, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 10));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 1, 31, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), 12);
    }

    public function itContinuesWhenTheEndDateIsOver() {
        $start_date = mktime(0, 0, 0, 1, 14, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 14));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 18, 2014)));
        $this->assertEqual($time_period->getNumberOfDaysUntilEnd(), -11);
    }

}

class TimePeriodWithoutWeekEnd_itDealsWithDuration extends TuleapTestCase {
    private $week_day_timestamp;
    private $following_week_day_timestamp;

    public function __construct() {
        $week_day                           = new DateTime('2016-02-01');
        $this->week_day_timestamp           = $week_day->getTimestamp();
        $following_week_day                 = new DateTime('2016-02-02');
        $this->following_week_day_timestamp = $following_week_day->getTimestamp();
    }

    public function itProcessesNegativeDuration() {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, -2);
        $this->assertEqual($time_period->getEndDate(), $this->week_day_timestamp);
    }

    public function itProcessesNullDuration() {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, 0);
        $this->assertEqual($time_period->getEndDate(), $this->week_day_timestamp);
    }

    public function itProcessesNullDurationWithAWeekEnd() {
        $week_end_day  = new DateTime('2016-02-06');
        $next_week_day = new DateTime('2016-02-08');
        $time_period   = new TimePeriodWithoutWeekEnd($week_end_day->getTimestamp(), 0);
        $this->assertEqual($time_period->getEndDate(), $next_week_day->getTimestamp());
    }

    public function itProcessesPositiveDuration() {
        $time_period = new TimePeriodWithoutWeekEnd($this->week_day_timestamp, 1);
        $this->assertEqual($time_period->getEndDate(), $this->following_week_day_timestamp);
    }
}

class TimePeriodWithWeekEnd_itDealsWithDuration extends TuleapTestCase {
    private $day_timestamp;
    private $following_day_timestamp;

    public function __construct() {
        $week_day                      = new DateTime('2016-02-01');
        $this->day_timestamp           = $week_day->getTimestamp();
        $following_day                 = new DateTime('2016-02-02');
        $this->following_day_timestamp = $following_day->getTimestamp();
    }

    public function itProcessesNegativeDuration() {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, -2);
        $this->assertEqual($time_period->getEndDate(), $this->day_timestamp);
    }

    public function itProcessesNullDuration() {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, 0);
        $this->assertEqual($time_period->getEndDate(), $this->day_timestamp);
    }

    public function itProcessesPositiveDuration() {
        $time_period = new TimePeriodWithWeekEnd($this->day_timestamp, 1);
        $this->assertEqual($time_period->getEndDate(), $this->following_day_timestamp);
    }
}

class TimePeriodTest extends TuleapTestCase
{
    public function itReturnsTrueWhenTodayIsBeforeFirstDayOfBurndown()
    {
        $day = new DateTime();
        $day->modify('+5 day');

        $time_period = new TimePeriodWithWeekEnd($day->getTimestamp(), 2);
        $this->assertTrue($time_period->isTodayBeforeTimePeriod());
    }

    public function itReturnsFalseWhenTodayIsAfterFirstDayOfBurndown()
    {
        $day = new DateTime();
        $day->modify('-5 day');

        $time_period = new TimePeriodWithWeekEnd($day->getTimestamp(), 2);
        $this->assertFalse($time_period->isTodayBeforeTimePeriod());
    }
}