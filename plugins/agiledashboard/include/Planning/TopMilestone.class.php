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

class Planning_TopMilestone  implements Planning_Milestone {

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

    private $duration;

    private $start_date;

    /**
     * @var Tracker_Artifact
     */
    private $artifact = null;

    public function __construct(Project $project, PFUser $user) {
        $this->project  = $project;
        $this->user = $user;

        $this->determineTrackers();
    }

    private function determineTrackers() {
        $tracker_manager = new TrackerManager();
        $trackers = $tracker_manager->getTrackersByGroupId($this->project->getID());

        $planning_factory = PlanningFactory::build();
        $plannings = $planning_factory->getPlanningsWithBacklogTracker($this->user, $this->project->getID());

        if (! $plannings) {
            throw new Planning_TopMilestoneNoPlanningsException('No Plannings Exist');
        }

        $first = current($plannings);

        $planning_tracker_id = $first->getPlanningTrackerId();
        $backlog_tracker_id  = $first->getBacklogTrackerId();

        $planning_tracker = $trackers[$planning_tracker_id];
        $backlog_tracker = $trackers[$backlog_tracker_id];

        $this->planning = new Planning(
            1,
            'name',
            $this->project->getID(),
            'my backlog_title',
            'my plan title',
            $backlog_tracker_id,
            $planning_tracker_id
        );

        $this->planning
            ->setPlanningTracker($planning_tracker)
            ->setBacklogTracker($backlog_tracker);
    }

    /**
     * @return string
     */
    public function getXRef() {
        if ($this->artifact) {
           return $this->artifact->getXRef();
        }

        return '';
    }

    public function getArtifact() {
        if ($this->artifact) {
            return $this->artifact;
        }

        return null;
    }

    public function getArtifactId() {
        if ($this->artifact) {
            return $this->artifact->getID();
        }

        return null;
    }

    public function setArtifact(Tracker_Artifact $artifact) {
        $this->artifact = $artifact;
    }

    public function getTrackerId() {
        return $this->planning->getBacklogTrackerId();
    }

    public function getArtifactTitle() {
        if ($this->artifact) {
            return $this->artifact->getTitle();
        }

        return null;
    }

    /**
     * @param PFUser $user
     * @return boolean
     */
    public function userCanView(PFUser $user) {
        if ($this->artifact) {
            return $this->artifact->getTracker()->userCanView($user);
        }

        return null;
    }

    public function getLinkedArtifacts(PFUser $user) {
    }

    public function getPlannedArtifacts() {
    }

    public function getPlanning() {
        return $this->planning;
    }

    public function getPlanningId() {
        return $this->planning->getId();
    }

    public function getProject() {
        return $this->project;
    }

    public function getGroupId() {
        return $this->project->getID();
    }

    public function hasAncestors() {
        return false;
    }

    public function getAncestors() {
        return array();
    }

    public function setAncestors(array $ancestors) {
    }

    public function setStartDate($start_date) {
        $this->start_date = $start_date;
        return $this;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
        return $this;
    }

    public function getEndDate() {
        if (! $this->start_date) {
            return null;
        }

        if (! $this->duration) {
            return null;
        }

        $end_date   = strtotime("+".floor($this->duration)." days", $this->start_date);

        return $end_date;
    }

    /**
     * @param PFUser $user
     * @return int | null
     */
    public function getCapacity(PFUser $user) {
        if (! $this->artifact) {
            return null;
        }

        $burndown_field = $this->artifact->getABurndownField($user);

        if ($burndown_field) {
            return $burndown_field->getCapacity($this->artifact);
        }

        return null;
    }
}
?>
