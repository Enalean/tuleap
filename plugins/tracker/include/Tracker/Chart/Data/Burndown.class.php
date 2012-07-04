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

class Tracker_Chart_Data_Burndown {
    private $duration;
    private $remaining_effort     = array();
    private $human_readable_dates = array();
    private $ideal_effort         = array();

    public function __construct($duration) {
        $this->duration = $duration;
    }

    public function addRemainingEffort($remaining_effort, $timestamp) {
        $this->remaining_effort[]     = $remaining_effort;
        $this->human_readable_dates[] = date('M-d', $timestamp);
    }

    public function getRemainingEffort() {
        return $this->remaining_effort;
    }

    public function getHumanReadableDates() {
        return $this->human_readable_dates;
    }

    public function getIdealEffort() {
        $start_effort = $this->remaining_effort[0];
        $slope = - ($start_effort / $this->duration);
        for($i = 0; $i < $this->duration; $i++) {
            $this->ideal_effort[] = floatval($slope * $i + $start_effort);
        }
        return $this->ideal_effort;
    }
}

?>
