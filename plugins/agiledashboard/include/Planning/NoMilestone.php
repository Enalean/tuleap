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

use Tuleap\Date\DatePeriodWithOpenDays;

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
    #[Override]
    public function getXRef()
    {
        return '';
    }

    #[Override]
    public function getArtifact()
    {
    }

    #[Override]
    public function getArtifactId()
    {
        return null;
    }

    #[Override]
    public function getTrackerId()
    {
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
        return true; // User can view milestone content, since it's empty.
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
    public function getGroupId()
    {
        return $this->project->getID();
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

    #[Override]
    public function getStartDate()
    {
        return null;
    }

    #[Override]
    public function getEndDate()
    {
        return null;
    }

    #[Override]
    public function getLastModifiedDate()
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
        return 'no-milestone';
    }
}
