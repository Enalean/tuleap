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

use Tuleap\Date\DatePeriodWithOpenDays;
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
    #[Override]
    public function getXRef()
    {
        return '';
    }

    #[Override]
    public function getArtifact()
    {
        return null;
    }

    #[Override]
    public function getArtifactId()
    {
        return null;
    }

    public function setArtifact(Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    #[Override]
    public function getTrackerId()
    {
        return $this->planning->getBacklogTrackersIds();
    }

    #[Override]
    public function getArtifactTitle()
    {
        return null;
    }

    /**
     * @return bool
     */
    #[Override]
    public function userCanView(PFUser $user)
    {
        return false;
    }

    #[Override]
    public function getLinkedArtifacts(PFUser $user)
    {
    }

    #[Override]
    public function getPlannedArtifacts()
    {
    }

    #[Override]
    public function getPlanning()
    {
        return $this->planning;
    }

    #[Override]
    public function getPlanningId()
    {
        return $this->planning->getId();
    }

    #[Override]
    public function getProject()
    {
        return $this->project;
    }

    #[Override]
    public function getGroupId(): int
    {
        return (int) $this->project->getID();
    }

    #[Override]
    public function hasAncestors()
    {
        return false;
    }

    #[Override]
    public function getAncestors()
    {
        return [];
    }

    #[Override]
    public function setAncestors(array $ancestors)
    {
    }

    public function setStartDate($start_date)
    {
    }

    #[Override]
    public function getStartDate()
    {
        return null;
    }

    public function setDuration($duration)
    {
    }

    #[Override]
    public function getEndDate()
    {
        return null;
    }

    #[Override]
    public function getCapacity()
    {
        return null;
    }

    #[Override]
    public function getRemainingEffort()
    {
        return null;
    }

    #[Override]
    public function getLastModifiedDate()
    {
        return null;
    }

    #[Override]
    public function getDuration()
    {
        return null;
    }

    #[Override]
    public function getParent()
    {
        return null;
    }

    #[Override]
    public function milestoneCanBeSubmilestone(Planning_Milestone $potential_submilestone)
    {
        return false;
    }

    #[Override]
    public function hasBurdownField(PFUser $user)
    {
        return false;
    }

    #[Override]
    public function getDaysSinceStart()
    {
        return 0;
    }

    #[Override]
    public function getDaysUntilEnd()
    {
        return 0;
    }

    #[Override]
    public function setDatePeriod(DatePeriodWithOpenDays $date_period): void
    {
    }

    #[Override]
    public function setCapacity($capacity)
    {
    }

    #[Override]
    public function setRemainingEffort($remaining_effort)
    {
    }

    #[Override]
    public function getPromotedMilestoneId(): string
    {
        return 'topbacklog';
    }
}
