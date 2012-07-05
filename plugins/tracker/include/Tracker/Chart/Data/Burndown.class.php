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

require_once 'BurndownTimePeriod.class.php';

class Tracker_Chart_Data_Burndown {

    /**
     * @var Tracker_Chart_Data_BurndownTimePeriod
     */
    private $time_period;

    private $remaining_effort = array();
    private $ideal_effort     = array();

    public function __construct(Tracker_Chart_Data_BurndownTimePeriod $time_period) {
        $this->time_period = $time_period;
    }

    private function getDuration() {
        return $this->time_period->getDuration();
    }

    /**
     * Add a new remaining effort value
     * 
     * @param type $remaining_effort 
     */
    public function addRemainingEffort($remaining_effort) {
        $this->remaining_effort[] = $remaining_effort;
        if($remaining_effort !== null && $this->remaining_effort[0] === null) {
            $this->fillInInitialRemainingEffortValues($remaining_effort);
        }
    }

    private function fillInInitialRemainingEffortValues($value) {
        for ($i = $this->getLastDayOffset(); $i >= 0; $i--) {
            $this->remaining_effort[$i] = $value;
        }
    }

    private function getLastDayOffset() {
        return count($this->remaining_effort) - 1;
    }

    /**
     * Returns the remaining effort values for each day to display on Burndown
     * 
     * @return Array
     */
    public function getRemainingEffort() {
        $remaining_effort = array();
        $current_day      = $this->time_period->getStartDate();
        $last_value       = null;

        foreach($this->time_period->getDayOffsets() as $day_offset) {

            if ($this->isInTheFutur($current_day)) {
                $remaining_effort[] = null;
            } else if (array_key_exists($day_offset, $this->remaining_effort)) {
                $remaining_effort[] = $this->remaining_effort[$day_offset];
            } else {
                $remaining_effort[] = $last_value;
            }

            $last_value  = $remaining_effort[$day_offset];
            $current_day = strtotime("+1 day", $current_day);
        }

        return $remaining_effort;
    }

    private function getFirstEffort() {
        foreach($this->remaining_effort as $effort) {
            if ($effort !== null) {
                return $effort;
            }
        }
        return null;
    }

    private function isInTheFutur($day) {
        return $day > $_SERVER['REQUEST_TIME'];
    }

    /**
     * Returns the Burndown dates in a human readable fashion
     * 
     * @return Array
     */
    public function getHumanReadableDates() {
        return $this->time_period->getHumanReadableDates();
    }

    /**
     * Returns the Ideal Burndown based on the initial remaining effort.
     * 
     * @return Array
     */
    public function getIdealEffort() {
        $start_effort = $this->getFirstEffort();
        foreach($this->time_period->getDayOffsets() as $day_offset) {
            $this->ideal_effort[] = $this->getIdealEffortAtDay($day_offset, $start_effort);
        }
        return $this->ideal_effort;
    }
    
    private function getIdealEffortAtDay($day, $start_effort) {
        if ($start_effort !== null) {
            $slope = - ($start_effort / $this->getDuration());
            return floatval($slope * $day + $start_effort);
        }
        return 0;
    }
}

?>
