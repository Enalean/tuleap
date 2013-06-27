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

function aMilestonePlan() {
    return new Test_Planning_MilestonePlanBuilder();
}

class Test_Planning_MilestonePlanBuilder {
    
    /**
     * @var Planning_Milestone[]
     */
    private $sub_milestones;

    private $milestone;

    private $capacity;

    private $remaining_effort;

    public function __construct() {
        $this->milestone        = aMilestone()->build();
        $this->sub_milestones   = array();
        $this->capacity         = 0 ;
        $this->remaining_effort = 0;
    }

    public function withMilestone(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
        return $this;
    }

    public function withRemainingEffort($remaining_effort) {
        $this->remaining_effort = $remaining_effort;
        return $this;
    }

    public function withCapacity($capacity) {
        $this->capacity = $capacity;
        return $this;
    }

    public function build() {
        return new Planning_MilestonePlan(
            $this->milestone,
            $this->sub_milestones,
            $this->capacity,
            $this->remaining_effort
        );
    }
}
?>
