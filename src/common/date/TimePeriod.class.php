<?php
/**
 * Copyright Enalean (c) 2011 - 2018. All rights reserved.
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

/**
 * A time period that has a start date and a duration
 */
abstract class TimePeriod
{
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
        $day_offsets = $this->getDayOffsets();
        $last_offset = end($day_offsets);
        return strtotime("+$last_offset days", $this->getStartDate());
    }

    /**
     * @return array of string
     */
    public function getHumanReadableDates() {
        $dates = array();

        foreach($this->getDayOffsets() as $day_offset) {
            $day     = strtotime("+$day_offset days", $this->getStartDate());
            $dates[] = date('D d', $day);
        }

        return $dates;
    }

    /**
     * To be used to iterate consistently over the time period
     *
     * @return array of int
     */
    public abstract function getDayOffsets();

    public abstract function getCountDayUntilDate($date);

    public function isTodayBeforeTimePeriod()
    {
        return $this->getStartDate() > $this->getTodayTimestamp();
    }

    /**
     * @return int
     */
    protected function getTodayTimestamp()
    {
        return strtotime($this->getTodayDate());
    }

    /**
     * Set to protected because it makes testing possible.
     */
    protected function getTodayDate()
    {
        if (isset($_SERVER['REQUEST_TIME'])) {
            return date('Y-m-d', $_SERVER['REQUEST_TIME']);
        }
        return date('Y-m-d');
    }
}
