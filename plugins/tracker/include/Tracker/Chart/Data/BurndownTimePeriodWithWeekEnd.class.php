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

/**
 * A burndown time period, starting at a given date, and with a given duration.
 */
class Tracker_Chart_Data_BurndownTimePeriodWithWeekEnd implements Tracker_Chart_Data_IProvideBurndownTimePeriod {

    /**
     * @var int The time period start date, as a Unix timestamp.
     */
    private $start_date;

    /**
     * @var int The time period duration, in days.
     */
    private $duration;

    public function __construct($start_date, $duration) {
        $this->start_date = $start_date;
        $this->duration   = $duration;
    }

    /**
     * @return int
     */
    public function getStartDate() {
        return $this->start_date;
    }

    /**
     * @return int
     */
    public function getDuration() {
        return $this->duration;
    }

    /**
     * @return array of string
     */
    public function getHumanReadableDates() {
        $dates = array();

        foreach($this->getDayOffsets() as $day_offset) {
            $day     = strtotime("+$day_offset days", $this->start_date);
            $dates[] = date('D d', $day);
        }

        return $dates;
    }

    /**
     * To be used to iterate consistently over burndown time period
     *
     * @return array of int
     */
    public function getDayOffsets() {
        return range(0, $this->duration);
    }
}

?>
