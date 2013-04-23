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
class Tracker_Chart_Data_BurndownTimePeriod {
    
    /**
     * @var int The time period start date, as a Unix timestamp.
     */
    private $start_date;
    
    /**
     * @var int The time period duration, in days.
     */
    private $duration;

    const FRIST_WEEK_END_DAY  = 'Sat';
    const SECOND_WEEK_END_DAY = 'Sun';

    public function __construct($start_date, $duration, $include_weekends) {
        $this->start_date = $start_date;
        $this->duration   = $duration;
        $this->include_weekends = $include_weekends;
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
     * @return boolean
     */
    public function includeWeekends() {
        return $this->include_weekends;
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
     * @return array of string
     */
    public function getHumanReadableDatesExcludingWeekends() {
        $dates = array();
        $day_offset = 0;
        while (count($dates)-1 != $this->duration) {
            $day = strtotime("+$day_offset days", $this->start_date);
            if ($this->dayIsInWeekEnd($day)) {
                $dates[] = date('D d', $day);
            }
            $day_offset ++;
        }
        return $dates;
    }
    
    /**
     * To be used to iterate consistently over burndown time period
     * 
     * @return array of int
     */
    public function getDayOffsets() {
        $day_offsets = range(0, $this->duration);
        if ($this->includeWeekends()) {
            return $day_offsets;
        }
        return $this->getDayOffsetsExcludingWeekends();
        
    }
    
    public function getDayOffsetsExcludingWeekends() {
        $day_offsets_excluding_we = array();
        $day_offset = 0;
        while (count($day_offsets_excluding_we)-1 != $this->duration) {
            $day = strtotime("+$day_offset days", $this->start_date);
            if ($this->dayIsInWeekEnd($day)) {
                $day_offsets_excluding_we[] = $day_offset;
            }
            $day_offset++;
            
       }
       return $day_offsets_excluding_we;
    }

    /**
     *
     * @param int $day timestamp
     * @return boolean
     */
    private function dayIsInWeekEnd($day) {
        return (date('D', $day) != self::FRIST_WEEK_END_DAY && date('D', $day) != self::SECOND_WEEK_END_DAY);
    }

}
?>
