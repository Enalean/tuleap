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
     * @var Tracker_Chart_Data_IProvideBurndownTimePeriod
     */
    private $time_period;

    private $remaining_effort = array();
    private $ideal_effort     = array();
    private $capacity         = null;

    public function __construct(Tracker_Chart_Data_IProvideBurndownTimePeriod $time_period, $capacity = null) {
        $this->time_period = $time_period;
        $this->capacity    = $capacity;
    }

    /**
     * Add a remaining effort at a given day offset
     *
     * @param Integer $day_offset
     * @param Float   $remaining_effort
     */
    public function addEffortAt($day_offset, $remaining_effort) {
        $this->remaining_effort[$day_offset] = $remaining_effort;
    }

    /**
     * Returns the remaining effort values for each day to display on Burndown
     *
     * @return Array
     */
    public function getRemainingEffort() {
        $remaining_effort = array();
        $previous_value   = null;
        $x_axis           = 0;
        foreach($this->time_period->getDayOffsets() as $day_offset) {
            $current_value = null;
            if ($this->isNotInTheFutur($day_offset)) {
                if ($this->hasRemainingEffortAt($day_offset)) {
                    $current_value = $this->remaining_effort[$day_offset];
                    $this->fillPreviousNullValues($previous_value, $current_value, $remaining_effort);
                } else {
                    $current_value = $previous_value;
                }
            }

            $remaining_effort[$x_axis] = $current_value;
            $previous_value = $current_value;
            $x_axis++;
        }
        return $remaining_effort;
    }

    private function hasRemainingEffortAt($day_offset) {
        return array_key_exists($day_offset, $this->remaining_effort);
    }

    private function fillPreviousNullValues($previous_value, $current_value, array &$remaining_effort) {
        $last_null_index = count($remaining_effort) - 1;
        if ($previous_value === null && $current_value !== null) {
            for ($i = $last_null_index; $i >= 0; $i--) {
                $remaining_effort[$i] = $current_value;
            }
        }
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
        $x_axis = 0;

        foreach($this->time_period->getDayOffsets() as $day_offset) {
            $this->ideal_effort[$x_axis] = $this->getIdealEffortAtDay($x_axis, $start_effort);
            $x_axis++;
        }
        return $this->ideal_effort;
    }

    private function getIdealEffortAtDay($i, $start_effort) {
        if ($start_effort !== null) {
            return floatval(($this->getDuration() - $i) * ($start_effort / $this->getDuration()));
        }
        return 0;
    }

    private function getDuration() {
        return $this->time_period->getDuration();
    }

    private function getFirstEffort() {
        if($this->capacity !== null && $this->capacity > 0) {
            return $this->capacity;
        }

        foreach($this->remaining_effort as $effort) {
            if ($effort !== null) {
                return $effort;
            }
        }
        
        return null;
    }

    private function isNotInTheFutur($day_offset) {
        return strtotime("+".$day_offset." day", $this->time_period->getStartDate()) <= $_SERVER['REQUEST_TIME'];
    }
}

?>
