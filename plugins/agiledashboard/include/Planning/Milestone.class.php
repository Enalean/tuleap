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
 * Describe what a Milestone business object should present.
 */
interface Planning_Milestone {
    const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';
    const CAPACITY_FIELD_NAME         = 'capacity';

    /**
     * @return int The project identifier.
     */
    public function getGroupId();

    /**
     * @return Project
     */
    public function getProject();

        /**
     * @return Tracker_Artifact
     */
    public function getArtifact();

    /**
     * @return array of Planning_Milestone
     */
    public function getSubMilestones();

    /**
     * @return Boolean True if milestone has at least 1 sub-milestone.
     */
    public function hasSubMilestones();

    /**
     * Adds some sub-milestones. Ignores milestones which are already a
     * sub-milestone of the current one.
     *
     * @param array $new_sub_milestones
     */
    public function addSubMilestones(array $new_sub_milestones);

    /**
     * @return Boolean
     */
    public function userCanView(User $user);

    /**
     * @return int
     */
    public function getTrackerId();

    /**
     * @return int
     */
    public function getArtifactId();

    /**
     * @return string
     */
    public function getArtifactTitle();

    /**
     * @return string
     */
    public function getXRef();


    /**
     * @return Planning
     */
    public function getPlanning();

    /**
     * @return int
     */
    public function getPlanningId();

    /**
     * @return TreeNode
     */
    public function getPlannedArtifacts();

    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts()
     * @param User $user
     * @return Array of Tracker_Artifact
     */
    public function getLinkedArtifacts(User $user);

    /**
     * Return numerical value of remaining effort
     *
     * @return float
     */
    public function getRemainingEffort();

    /**
     * Return numerical value of capacity
     *
     * @return float
     */
    public function getCapacity();

    /**
     * Return true of the milestone is inside hierarchy.
     *
     * Example: a root node (a product) is not IN hierarchy
     *          a lonely milestone is not either.
     *
     * @return boolean
     */
    public function hasAncestors();

    /**
     * Return all parents of current milestone
     *
     * @return Array of Planning_Milestone
     */
    public function getAncestors();

    /**
     * Set parents of current milestone
     *
     * @param Array of Planning_Milestone
     */
    public function setAncestors(array $ancestors);
}

?>
