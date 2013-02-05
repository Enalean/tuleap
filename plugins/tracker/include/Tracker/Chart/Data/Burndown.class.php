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
 * Storage data for Burndown display via JPgraph
 */
class Tracker_Chart_Data_Burndown {

    /**
     * @var Tracker_Chart_Data_BurndownTimePeriod
     */
    private $time_period;

    private $remaining_effort = array();
    private $ideal_effort     = array();
    private $capacity = null;

    public function __construct(Tracker_Chart_Data_BurndownTimePeriod $time_period, $capacity = null) {
        $this->time_period = $time_period;
        $this->capacity = $capacity;
    }

    /**
     * Stack a new remaining effort value
     *
     * @param Float|Null $remaining_effort
     */
    public function pushRemainingEffort($remaining_effort) {
        $this->remaining_effort[] = $remaining_effort;
        if($remaining_effort !== null && $this->remaining_effort[0] === null) {
            $this->fillInInitialRemainingEffortValues($remaining_effort);
        }
    }

    private function fillInInitialRemainingEffortValues($value) {
        $last_day_offset = count($this->remaining_effort) - 1;
        for ($i = $last_day_offset; $i >= 0; $i--) {
            $this->remaining_effort[$i] = $value;
        }
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

    private function getDuration() {
        return $this->time_period->getDuration();
    }

    private function getFirstEffort() {
        if($this->capacity !== null) {
            return $this->capacity;
        }

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
}

?>
