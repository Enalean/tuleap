<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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


class TimePeriodWithoutWeekEnd  extends TimePeriod {
    /**
     * To be used to iterate consistently over the time period
     *
     * @return array of int
     */
    public function getDayOffsets() {
        if ($this->getDuration() <= 0) {
            return $this->getDayOffsetsWithInconsistentDuration();
        } else {
            return $this->getDayOffsetsWithConsistentDuration();
        }
    }

    public function getCountDayUntilDate($date)
    {
        if ($date < $this->getEndDate()) {
            return $this->getNumberOfDaysWithoutWeekEnd($this->getStartDate(), $date);
        } else {
            return count($this->getDayOffsets());
        }
    }

    /**
     * @return array
     */
    private function getDayOffsetsWithConsistentDuration() {
        $day_offsets_excluding_we = array();
        $day_offset = 0;
        while (count($day_offsets_excluding_we)-1 != $this->getDuration()) {
            $day = $this->getNextDay($day_offset, $this->getStartDate());
            if ( $this->isNotWeekendDay($day)) {
                $day_offsets_excluding_we[] = $day_offset;
            }
            $day_offset++;
        }
        return $day_offsets_excluding_we;
    }

    /**
     * @return array
     */
    private function getDayOffsetsWithInconsistentDuration() {
        $day_offset = 0;
        $day        = $this->getNextDay($day_offset, $this->getStartDate());
        while (! $this->isNotWeekendDay($day)) {
            $day_offset++;
            $day = $this->getNextDay($day_offset, $this->getStartDate());
        }

        return array($day_offset);
    }

    private function getNextDay($next_day_number, $date) {
        return strtotime("+$next_day_number days", $date);
    }

    public function isNotWeekendDay($day) {
        return ! ((int) date('N', $day) === 6 || (int) date('N', $day) === 7);
    }

    /**
     * The number of days until the end of the period
     *
     * @return int
     */
    public function getNumberOfDaysUntilEnd() {
        if ($this->getTodayTimestamp() > $this->getEndDate()) {
            return -$this->getNumberOfDaysWithoutWeekEnd($this->getEndDate(), $this->getTodayTimestamp());
        } else {
            return $this->getNumberOfDaysWithoutWeekEnd($this->getTodayTimestamp(), $this->getEndDate());
        }
    }

    private function getNumberOfDaysWithoutWeekEnd($start_date, $end_date) {
        $real_number_of_days_after_start = 0;
        $day        = $start_date;
        if ($this->isNotWeekendDay($day)) {
            $day_offset = -1;
        } else {
            $day_offset = 0;
        }

        do {
            if ($this->isNotWeekendDay($day)) {
                $day_offset++;
            }
            $day = $this->getNextDay($real_number_of_days_after_start, $start_date);
            $real_number_of_days_after_start++;
        } while ($day < $end_date);

        return $day_offset;
    }

    /**
     * The number of days since the start.
     * Is not limited by the duration of the time period.
     *
     * @return int
     */
    public function getNumberOfDaysSinceStart() {
        if ($this->isToday($this->getStartDate()) || $this->getStartDate() > $this->getTodayTimestamp()) {
            return 0;
        }

        return $this->getNumberOfDaysWithoutWeekEnd($this->getStartDate(), $this->getTodayTimestamp());
    }

    private function isToday($day) {
        return $this->getTodayDate() == date('Y-m-d', $day);
    }

    /**
     * @return boolean
     */
    public function isTodayWithinTimePeriod() {
        if ($this->getStartDate() <= $this->getTodayTimestamp() &&
            $this->getNumberOfDaysSinceStart() <= $this->getDuration()
        ) {
            return true;
        }

        return false;
    }
}