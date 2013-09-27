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

require_once 'TimePeriod.class.php';

class TimePeriodWithoutWeekEnd  implements TimePeriod {
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
     * @return int
     */
    public function getEndDate() {
        $last_offset = end($this->getDayOffsets());
        return strtotime("+$last_offset days", $this->start_date);
    }

    /**
     * @return array of string
     */
    public function getHumanReadableDates() {
        $dates = array();
        $day_offset = 0;
        while (count($dates)-1 != $this->duration) {
            $day = $this->getNextDay($day_offset, $this->start_date);
            $day_offset++;
            if ( $this->isNotWeekendDay($day)) {
                $dates[] = date('D d', $day);
            }
        }
        return $dates;
    }

    /**
     * To be used to iterate consistently over the time period
     *
     * @return array of int
     */
    public function getDayOffsets() {
        $day_offsets_excluding_we = array();
        $day_offset = 0;
        while (count($day_offsets_excluding_we)-1 != $this->duration) {
            $day = $this->getNextDay($day_offset, $this->start_date);
            if ( $this->isNotWeekendDay($day)) {
                $day_offsets_excluding_we[] = $day_offset;
            }
            $day_offset++;
       }
       return $day_offsets_excluding_we;
    }

    private function getNextDay($next_day_number, $date) {
        return strtotime("+$next_day_number days", $date);
    }

    private function isNotWeekendDay($day) {
        return ! (date('D', $day) == 'Sat' || date('D', $day) == 'Sun');
    }
}

?>
