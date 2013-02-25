<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * This class is responsible of the "Plan" part of a MilestonePlanning (the right pane)
 */
class Planning_MilestonePlan {
    /**
     * A sub-milestone is a decomposition of the current one.
     *
     * Given current Milestone is a Release
     * And there is a Parent/Child association between Release and Sprint
     * Then $sub_milestone will be an array of sprint
     *
     * @var array of Planning_Milestone
     */
    private $sub_milestones = array();

    /**
     * The effort needed to complete the milestone. It's a numerical quantification
     * of the workload.
     *
     * @var Float
     */
    private $remaining_effort = null;

    /**
     * The estimated workforce of the team for given milestone.
     * It's set at the beginning of the Milestone and shall not change during its life.
     *
     * @var Float
     */
    private $capacity = null;

    /**
     * @var Planning_ArtifactMilestone
     */
    private $milestone;

    public function __construct(
            Planning_ArtifactMilestone $milestone,
            array $sub_milestones,
            $capacity,
            $remaining_effort
            ) {
        $this->milestone        = $milestone;
        $this->sub_milestones   = $sub_milestones;
        $this->capacity         = $capacity;
        $this->remaining_effort = $remaining_effort;
    }

    /**
     * @return Planning_ArtifactMilestone
     */
    public function getMilestone() {
        return $this->milestone;
    }

    /**
     * @return array of Planning_Milestone
     */
    public function getSubMilestones() {
        return $this->sub_milestones;
    }

    /**
     * @return Boolean True if milestone has at least 1 sub-milestone.
     */
    public function hasSubMilestones() {
        return ! empty($this->sub_milestones);
    }

    public function getRemainingEffort() {
        return $this->remaining_effort;
    }

    /**
     * @param float $remaining_effort
     *
     * @return Planning_ArtifactMilestone
     */
    public function setRemainingEffort($remaining_effort) {
        $this->remaining_effort = $remaining_effort;
        return $this;
    }

    public function getCapacity() {
        return $this->capacity;
    }

    /**
     * @param float $capacity
     *
     * @return Planning_ArtifactMilestone
     */
    public function setCapacity($capacity) {
        $this->capacity = $capacity;
        return $this;
    }
}

?>
