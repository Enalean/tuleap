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

    public function itComputesDateBasedOnStartDate() {
        $start_date    = mktime(0, 0, 0, 7, 4, 2012);
        $time_period   = new TimePeriodWithWeekEnd($start_date, 2);

        $this->assertEqual($time_period->getHumanReadableDates(), array('Wed 04', 'Thu 05', 'Fri 06'));
    }

    public function itProvidesAListOfTheDayOffsetsInTheTimePeriod() {
        $start_date    = mktime(0, 0, 0, 7, 4, 2012);
        $time_period   = new TimePeriodWithWeekEnd($start_date, 2);

        $this->assertEqual($time_period->getDayOffsets(), array(0, 1, 2));
    }
}

class TimePeriodWithoutWeekEndTest extends TuleapTestCase {

    public function itProvidesAListOfDaysWhileExcludingWeekends() {
        $start_date    = mktime(0, 0, 0, 7, 4, 2012);
        $time_period   = new TimePeriodWithoutWeekEnd($start_date, 4);

        $this->assertEqual($time_period->getHumanReadableDates(), array('Wed 04', 'Thu 05', 'Fri 06', 'Mon 09', 'Tue 10'));
    }
}
?>
