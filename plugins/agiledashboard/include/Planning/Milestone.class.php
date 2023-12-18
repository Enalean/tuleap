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
use Tuleap\Tracker\Artifact\Artifact;

/**
 * Describe what a Milestone business object should present.
 */
interface Planning_Milestone // @codingStandardsIgnoreLine
{
    public const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';
    public const CAPACITY_FIELD_NAME         = 'capacity';
    public const DURATION_FIELD_NAME         = 'duration';

    /**
     * @return int The project identifier.
     */
    public function getGroupId();

    /**
     * @return Project
     */
    public function getProject();

        /**
     * @return Artifact
     */
    public function getArtifact();

    /**
     * @return bool
     */
    public function userCanView(PFUser $user);

    /**
     * @return int|int[]
     */
    public function getTrackerId();

    /**
     * @return int|null
     */
    public function getArtifactId();

    /**
     * @return string|null
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
     * @return ArtifactNode
     */
    public function getPlannedArtifacts();

    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts()
     * @return Artifact[]
     */
    public function getLinkedArtifacts(PFUser $user);

    /**
     * Return true of the milestone is inside hierarchy.
     *
     * Example: a root node (a product) is not IN hierarchy
     *          a lonely milestone is not either.
     *
     * @return bool
     */
    public function hasAncestors();

    /**
     * Return all parents of current milestone
     *
     * @return Array of Planning_Milestone
     */
    public function getAncestors();

    /**
     * Return first parent of current milestone
     *
     * @return Planning_Milestone | null
     */
    public function getParent();

    /**
     * Set parents of current milestone
     *
     * @param Array of Planning_Milestone
     */
    public function setAncestors(array $ancestors);

    /**
     * Get the start date of the milestone
     *
     * @return int|null the timestamp value of start_date field
     */
    public function getStartDate();

    /**
     * Get the end date of the milestone
     *
     * @return int|null the timestamp value of the duration field
     */
    public function getEndDate();

    public function getDaysSinceStart();

    public function getDaysUntilEnd();

    /**
     * Get the timestamp of the last modification of the milestone
     *
     * @return int|null timestamp
     */
    public function getLastModifiedDate();

    /**
     * Return milestone duration in days
     *
     * @return int|null
     */
    public function getDuration();

    /**
     * Return capacity
     *
     * @return float|null
     */
    public function getCapacity();

    /**
     * Return remaining_effort
     *
     * @return float|null
     */
    public function getRemainingEffort();

    /**
     * Return If type of element is compatible.
     *
     * @return bool
     */
    public function milestoneCanBeSubmilestone(Planning_Milestone $potential_submilestone);

    public function hasBurdownField(PFUser $user);

    public function setDatePeriod(DatePeriodWithoutWeekEnd $date_period): void;

    public function setCapacity($capacity);

    public function setRemainingEffort($remaining_effort);

    public function getPromotedMilestoneId(): string;
}
