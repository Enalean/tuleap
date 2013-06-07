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
class Planning_VirtualTopMilestone  implements Planning_Milestone {

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

    /**
     * @param Project $project
     * @param PFUser $user
     * @param TrackerFactory $tracker_factory
     * @throws Planning_VirtualTopMilestoneNoPlanningsException
     */
    public function __construct(Project $project, PFUser $user, TrackerFactory $tracker_factory, PlanningFactory $planning_factory) {
        $this->project = $project;
        $this->user    = $user;

        $this->generatePlanning($tracker_factory, $planning_factory);
    }

    private function generatePlanning(TrackerFactory $tracker_factory, PlanningFactory $planning_factory) {
        $project_plannings = $planning_factory->getOrderedPlanningsWithBacklogTracker($this->user, $this->project->getID());
        if (! $project_plannings) {
            throw new Planning_VirtualTopMilestoneNoPlanningsException('No Plannings Exist');
        }
        // Currently just take the first
        $first_planning = current($project_plannings);
        $project_trackers = $tracker_factory->getTrackersByGroupId($this->project->getID());

        $planning_tracker_id = $first_planning->getPlanningTrackerId();
        $backlog_tracker_id  = $first_planning->getBacklogTrackerId();

        $planning_tracker = $project_trackers[$planning_tracker_id];
        $backlog_tracker  = $project_trackers[$backlog_tracker_id];

        $this->planning = new Planning(
            $first_planning->getId(),
            null,
            $this->project->getID(),
            null,
            null,
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
        return '';
    }

    public function getArtifact() {
        return null;
    }

    public function getArtifactId() {
        return null;
    }

    public function setArtifact(Tracker_Artifact $artifact) {
        $this->artifact = $artifact;
    }

    public function getTrackerId() {
        return $this->planning->getBacklogTrackerId();
    }

    public function getArtifactTitle() {
        return null;
    }

    /**
     * @param PFUser $user
     * @return boolean
     */
    public function userCanView(PFUser $user) {
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
    }

    public function getStartDate() {
        return null;
    }

    public function setDuration($duration) {
    }

    public function getEndDate() {
        return null;
    }

    public function getCapacity(PFUser $user) {
        return null;
    }
}
?>
