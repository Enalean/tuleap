<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\REST\Artifact\BurndownRepresentation;

/**
 * Storage data for Burndown display
 */
class Tracker_Chart_Data_Burndown
{
    /**
     * @var array
     */
    private $remaining_efforts_at_date;

    private DatePeriodWithoutWeekEnd $date_period;

    private $remaining_effort;
    private $ideal_effort;
    private $capacity;
    private $is_under_calcul;

    public function __construct(DatePeriodWithoutWeekEnd $time_period, $capacity = null, $is_under_calcul = false)
    {
        $this->date_period               = $time_period;
        $this->capacity                  = $capacity;
        $this->is_under_calcul           = $is_under_calcul;
        $this->remaining_effort          = [];
        $this->ideal_effort              = [];
        $this->remaining_efforts_at_date = [];
    }

    /**
     * Add a remaining effort at a given day offset
     *
     * @param int $day_offset
     * @param Float   $remaining_effort
     */
    public function addEffortAt($day_offset, $remaining_effort)
    {
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
     *
     * @psalm-mutation-free
     */
    public function getRemainingEffort()
    {
        $remaining_effort = [];

        if ($this->date_period->isTodayBeforeDatePeriod()) {
            $remaining_effort[] = null;

            return $remaining_effort;
        }

        $number_of_days = $this->date_period->getCountDayUntilDate($_SERVER['REQUEST_TIME']);
        for ($day_offset = 0; $day_offset < $number_of_days; $day_offset++) {
            if (isset($this->remaining_effort[$day_offset])) {
                $remaining_effort[] = $this->remaining_effort[$day_offset];
            } else {
                $remaining_effort[] = null;
            }
        }

        if ($number_of_days === 0) {
            $remaining_effort[] = null;
        }

        return $remaining_effort;
    }

    /**
     * Returns the Burndown dates in a human readable fashion
     *
     * @return Array
     */
    public function getHumanReadableDates()
    {
        return $this->date_period->getHumanReadableDates();
    }

    /**
     * Returns the Ideal Burndown based on the initial remaining effort.
     *
     * @return Array
     */
    public function getIdealEffort()
    {
        $start_effort = $this->getFirstEffort();
        $x_axis       = 0;

        foreach ($this->date_period->getDayOffsets() as $day_offset) {
            $this->ideal_effort[$x_axis] = $this->getIdealEffortAtDay($x_axis, $start_effort);
            $x_axis++;
        }
        return $this->ideal_effort;
    }

    /**
     * @return BurndownRepresentation
     */
    public function getRESTRepresentation()
    {
        return new BurndownRepresentation($this);
    }

    public function getJsonRepresentation()
    {
        $values = [
            'duration' => $this->getDuration(),
            'capacity' => $this->getCapacityValueInJson(),
            'points'   => $this->getRemainingEffortWithoutNullValues(),
        ];

        return json_encode($values);
    }

    private function getCapacityValueInJson()
    {
        return isset($this->capacity) ? $this->capacity : 'null';
    }

    public function getRemainingEffortWithoutNullValues()
    {
        if ($this->is_under_calcul === true) {
            return [];
        }

        return $this->removeNullRemainingEffort($this->getRemainingEffort());
    }

    private function removeNullRemainingEffort($remaining_efforts)
    {
        $remaining_effort_without_null_values = [];

        foreach ($remaining_efforts as $remaining_effort) {
            if ($remaining_effort !== null) {
                $remaining_effort_without_null_values[] = $remaining_effort;
            }
        }

        return $remaining_effort_without_null_values;
    }

    private function getIdealEffortAtDay($i, $start_effort)
    {
        if ($start_effort !== null) {
            return floatval(($this->getDuration() - $i) * ($start_effort / $this->getDuration()));
        }
        return 0;
    }

    private function getDuration()
    {
        return $this->date_period->getDuration();
    }

    public function getDatePeriod(): DatePeriodWithoutWeekEnd
    {
        return $this->date_period;
    }

    private function getFirstEffort()
    {
        if ($this->capacity !== null && $this->capacity > 0) {
            return $this->capacity;
        }

        foreach ($this->remaining_effort as $effort) {
            if ($effort !== null) {
                return $effort;
            }
        }

        return null;
    }

    /**
     * @return null
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @return bool
     */
    public function isUnderCalcul()
    {
        return $this->is_under_calcul;
    }

    public function addEffortAtDateTime(DateTime $date, $remaining_effort)
    {
        $this->remaining_efforts_at_date[$date->getTimestamp()] = $remaining_effort;
    }

    /**
     * @return array
     */
    public function getRemainingEffortsAtDate()
    {
        return $this->remaining_efforts_at_date;
    }
}
