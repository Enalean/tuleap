<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * This class represents a virtual TopMilestone
 *
 * In essence, it is a milestone that sits above all other milestones in a
 * hierarchy.
 *
 * Example 1: Say your only milestones are called sprints. Then collections
 * of sprints will not be defined. So, this virtual top milestone will represent the
 * collection of all sprints.
 *
 * Example 2: Say you have milestones called releases and each release has a set
 * of sprints. In this case, collections/ sets of releases will not be defined.
 * Thus, this virtual milestone will represent the set of all releases.
 *
 * Because of all this, a Planning_VirtualTopMilestone does not correspond to any
 * Tracker_Artifact; there is no artifact that represents this milestone or
 * vice-versa. Hence, most of the properties of a virtual milestone are irrelevant
 * and null
 *
 */
class Planning_VirtualTopMilestone implements Planning_Milestone
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
        return null;
    }

    public function getArtifactId()
    {
        return null;
    }

    public function setArtifact(Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    public function getTrackerId()
    {
        return $this->planning->getBacklogTrackersIds();
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
        return false;
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

    public function setStartDate($start_date)
    {
    }

    public function getStartDate()
    {
        return null;
    }

    public function setDuration($duration)
    {
    }

    public function getEndDate()
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

    public function getLastModifiedDate()
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

    public function getBurndownData(PFUser $user)
    {
        return null;
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
}
