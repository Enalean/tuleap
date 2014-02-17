<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class TimePeriodWithoutWeekEnd_getNumberOfDurationDaysSinceStartTest extends TuleapTestCase {

    public function itDoesNotReturnMoreDaysThanTheDuration() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 18));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2015)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 18);
    }

    public function itDoesNotCountTheStartDate() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 8));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 1, 31, 2014)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 0);
    }

    public function itCountsTheNextDayAsOneDay() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 8));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 4, 2014)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 1);
    }

    public function itCountsAWeekAsFiveDays() {
        $start_date = mktime(0, 0, 0, 2, 3, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 8));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 5);
    }

    public function itCountsAWeekendAsNothing() {
        $start_date = mktime(0, 0, 0, 2, 7, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 8));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 10, 2014)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 1);
    }

    public function itExcludesAllTheWeekends() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 8));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2014)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 8);
    }

    public function itIgnoresFutureStartDates() {
        $start_date = mktime(0, 0, 0, 1, 31, 2014);
        $time_period = partial_mock('TimePeriodWithoutWeekEnd', array('getTodayDate'), array($start_date, 15));
        stub($time_period)->getTodayDate()->returns(date('Y-m-d', mktime(0, 0, 0, 2, 27, 2013)));
        $this->assertEqual($time_period->getNumberOfDurationDaysSinceStart(), 0);
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
?>
