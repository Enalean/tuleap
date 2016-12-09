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

require_once 'common/date/TimePeriod.class.php';

/**
 * Storage data for Burndown display via JPgraph
 */
class Tracker_Chart_Data_Burndown
{
    /**
     * @var TimePeriod
     */
    private $time_period;

    private $remaining_effort;
    private $ideal_effort;
    private $capacity;
    private $is_under_calcul;

    public function __construct(TimePeriod $time_period, $capacity = null, $is_under_calcul = false)
    {
        $this->time_period      = $time_period;
        $this->capacity         = $capacity;
        $this->is_under_calcul  = $is_under_calcul;
        $this->remaining_effort = array();
        $this->ideal_effort     = array();
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

    public function setIsBeingCalculated($is_under_calcul)
    {
        $this->is_under_calcul = $is_under_calcul;
    }

    public function isBeingCalculated()
    {
        return $this->is_under_calcul;
    }

    /**
     * Returns the remaining effort values for each day to display on Burndown
     *
     * @return Array
     */
    public function getRemainingEffort()
    {
        $remaining_effort = array();

        if ($this->time_period->isTodayBeforeTimePeriod()) {
            $remaining_effort[] = null;

            return $remaining_effort;
        }

        $number_of_days = $this->time_period->getCountDayUntilDate($_SERVER['REQUEST_TIME']);
        for ($day_offset = 0; $day_offset < $number_of_days; $day_offset++) {
            if (isset($this->remaining_effort[$day_offset])) {
                $remaining_effort[] = $this->remaining_effort[$day_offset];
            } else {
                $remaining_effort[] = null;
            }
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
        $x_axis = 0;

        foreach($this->time_period->getDayOffsets() as $day_offset) {
            $this->ideal_effort[$x_axis] = $this->getIdealEffortAtDay($x_axis, $start_effort);
            $x_axis++;
        }
        return $this->ideal_effort;
    }

    /**
     * @return \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
     */
    public function getRESTRepresentation() {
        $classname = '\Tuleap\Tracker\REST\Artifact\BurndownRepresentation';
        $burndown = new $classname;
        return $burndown->build(
            $this->getDuration(),
            $this->capacity,
            $this->getRemainingEffortWithoutNullValues(),
            $this->is_under_calcul
        );
    }

    public function getJsonRepresentation() {
        $values = array(
            'duration' => $this->getDuration(),
            'capacity' => $this->getCapacityValueInJson(),
            'points'   => $this->getRemainingEffortWithoutNullValues()
        );

        return json_encode($values);
    }

    private function getCapacityValueInJson() {
        return isset($this->capacity) ? $this->capacity : 'null';
    }

    private function getRemainingEffortWithoutNullValues() {
        if ($this->is_under_calcul === true) {
            return array();
        }

        return array_filter($this->getRemainingEffort(), array($this, 'isValueNotNull'));
    }

    private function isValueNotNull($value) {
        return $value !== null;
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

    /**
     * @return  TimePeriod
     */
    public function getTimePeriod()
    {
        return $this->time_period;
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
}
