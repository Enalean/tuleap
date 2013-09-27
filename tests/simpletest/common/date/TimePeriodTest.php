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
?>
