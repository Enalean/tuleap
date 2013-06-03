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

    public function __construct(Project $project, Codendi_Request $request) {
        $this->project  = $project;
        $this->request = $request;

        $this->determineTrackers();
    }

    private function determineTrackers() {
        $tracker_manager = new TrackerManager();
        $trackers = $tracker_manager->getTrackersByGroupId($this->project->getID());

        $top_trackers_ids     = $this->getTopTrackerIds($trackers);
        $planning_tracker_ids = $this->getPlanningTrackerIds();

        $planning_tracker_id = null;
        $backlog_tracker_id  = null;
        foreach ($top_trackers_ids as $top_tracker_id) {
            if (in_array($top_tracker_id, $planning_tracker_ids)) {
                $planning_tracker_id = $top_tracker_id;
            } else {
                $backlog_tracker_id = $top_tracker_id;
            }
        }

        $planning_tracker = $trackers[$planning_tracker_id];
        $backlog_tracker = $trackers[$backlog_tracker_id];

        $this->planning = new Planning(
            1,
            'name',
            $this->project->getID(),
            'my backlog_title',
            'my plan title',
            $planning_tracker_id,
            $backlog_tracker_id
        );

        $this->planning->setPlanningTracker($planning_tracker);
        $this->planning->setBacklogTracker($backlog_tracker);
    }

    private function getPlanningTrackerIds() {
        $user = $this->request->getCurrentUser();
        $planning_factory = PlanningFactory::build();
        $plannings = $planning_factory->getPlannings($user, $this->project->getID());

        $planning_tracker_ids = array();
        foreach ($plannings as $planning) {
            $planning_tracker_ids[] = $planning->getPlanningTrackerId();
        }

        return $planning_tracker_ids;
    }

    public function getTopTrackerIds($trackers) {
        $hierarchy_factory = Tracker_HierarchyFactory::instance();

        $tracker_ids = array();
        foreach ($trackers as $planning_tracker) {
            $tracker_ids[] = $planning_tracker->getId();
        }

        $hierarchy = $hierarchy_factory->getHierarchy($tracker_ids);

        $top_trackers_ids = array();
        foreach ($tracker_ids as $id) {
            if($hierarchy->isRoot($id)) {
                $top_trackers_ids[] = $id;
            }
        }

        return $top_trackers_ids;
    }

    /**
     * @return string
     */
    public function getXRef() {
        return '';
    }

    public function getArtifact() {

    }

    public function getArtifactId() {
        return null;
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
        return true; // User can view milestone content, since it's empty.
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

    public function getStartDate() {
        return null;
    }

    public function getEndDate() {
        return null;
    }
}
?>
