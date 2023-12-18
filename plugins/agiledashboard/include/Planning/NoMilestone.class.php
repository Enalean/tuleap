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

/**
 * Null-object pattern for planning milestones.
 */
class Planning_NoMilestone implements Planning_Milestone
{
    /**
     * The project where the milestone is defined
     *
     * @var Project
     */
    private $project;

    /**
     * The association between the tracker that define the "Content" (aka Backlog) (ie. Epic)
     * and the tracker that define the plan (ie. Release)
     *
     * @var Planning
     */
    private $planning;

    public function __construct(Project $project, Planning $planning)
    {
        $this->project  = $project;
        $this->planning = $planning;
    }

    /**
     * @return string
     */
    public function getXRef()
    {
        return '';
    }

    public function getArtifact()
    {
    }

    public function getArtifactId()
    {
        return null;
    }

    public function getTrackerId()
    {
    }

    public function getArtifactTitle()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function userCanView(PFUser $user)
    {
        return true; // User can view milestone content, since it's empty.
    }

    public function getLinkedArtifacts(PFUser $user)
    {
    }

    public function getPlannedArtifacts()
    {
    }

    public function getPlanning()
    {
        return $this->planning;
    }

    public function getPlanningId()
    {
        return $this->planning->getId();
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getGroupId()
    {
        return $this->project->getID();
    }

    public function hasAncestors()
    {
        return false;
    }

    public function getAncestors()
    {
        return [];
    }

    public function setAncestors(array $ancestors)
    {
    }

    public function getStartDate()
    {
        return null;
    }

    public function getEndDate()
    {
        return null;
    }

    public function getLastModifiedDate()
    {
        return null;
    }

    public function getCapacity()
    {
        return null;
    }

    public function getRemainingEffort()
    {
        return null;
    }

    public function getDuration()
    {
        return null;
    }

    public function getParent()
    {
        return null;
    }

    public function milestoneCanBeSubmilestone(Planning_Milestone $potential_submilestone)
    {
        return false;
    }

    public function hasBurdownField(PFUser $user)
    {
        return false;
    }

    public function getDaysSinceStart()
    {
        return 0;
    }

    public function getDaysUntilEnd()
    {
        return 0;
    }

    public function setDatePeriod(DatePeriodWithoutWeekEnd $date_period): void
    {
    }

    public function setCapacity($capacity)
    {
    }

    public function setRemainingEffort($remaining_effort)
    {
    }

    public function getPromotedMilestoneId(): string
    {
        return 'no-milestone';
    }
}
