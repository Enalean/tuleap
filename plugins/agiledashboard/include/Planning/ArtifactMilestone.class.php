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

require_once 'common/date/TimePeriodWithoutWeekEnd.class.php';

/**
 * A planning milestone (e.g.: Sprint, Release...)
 */
class Planning_ArtifactMilestone implements Planning_Milestone {

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
     * The artifact that represent the milestone
     *
     * For instance a Sprint or a Release
     *
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * The planned artifacts are the content of the milestone (stuff to be done)
     *
     * Given current Milestone is a Sprint
     * And I defined a Sprint planning that associate Stories to Sprints
     * Then I will have an array of Sprint as planned artifacts.
     *
     * @var ArtifactNode
     */
    private $planned_artifacts;

    /**
     * A parent milestone is the milestone that contains the current one.
     *
     * Given current Milestone is a Sprint
     * And there is a Parent/Child association between Release and Sprint
     * And there is a Parent/Child association between Product and Release
     * Then $parent_milestones will be a Release and a Product
     *
     * @var array of Planning_Milestone
     */
    private $parent_milestones = array();

    /**
     * The duration before hitting the end date of the milestone.
     *
     * @var Int
     */
     private $duration = null;

    /**
     * The start date of the milestone
     *
     * @var String
     */
     private $start_date = null;

    /**
     * The capacity of the milestone
     *
     * @var float
     */
     private $capacity = null;

    /**
     * @param Project $project
     * @param Planning $planning
     * @param Tracker_Artifact $artifact
     * @param TreeNode $planned_artifacts
     */
    public function __construct(Project          $project,
                                Planning         $planning,
                                Tracker_Artifact $artifact,
                                ArtifactNode     $planned_artifacts = null) {

        $this->project           = $project;
        $this->planning          = $planning;
        $this->artifact          = $artifact;
        $this->planned_artifacts = $planned_artifacts;
    }

    /**
     * @return int The project identifier.
     */
    public function getGroupId() {
        return $this->project->getID();
    }

    /**
     * @return Project
     */
    public function getProject() {
        return $this->project;
    }

        /**
     * @return Tracker_Artifact
     */
    public function getArtifact() {
        return $this->artifact;
    }

    /**
     * @return Boolean
     */
    public function userCanView(PFUser $user) {
        return $this->artifact->getTracker()->userCanView($user);
    }

    /**
     * @return int
     */
    public function getTrackerId() {
        return $this->artifact->getTrackerId();
    }

    /**
     * @return int
     */
    public function getArtifactId() {
        return $this->artifact->getId();
    }

    /**
     * @return string
     */
    public function getArtifactTitle() {
        return $this->artifact->getTitle();
    }

    /**
     * @return string
     */
    public function getXRef() {
        return $this->artifact->getXRef();
    }


    /**
     * @return Planning
     */
    public function getPlanning() {
        return $this->planning;
    }

    /**
     * @return int
     */
    public function getPlanningId() {
        return $this->planning->getId();
    }

    /**
     * @return ArtifactNode
     */
    public function getPlannedArtifacts() {
        return $this->planned_artifacts;
    }

    /**
     * @param ArtifactNode $node
     */
    public function setPlannedArtifacts(ArtifactNode $node) {
        $this->planned_artifacts = $node;
    }

    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts()
     * @param PFUser $user
     * @return Tracker_Artifact[]
     */
    public function getLinkedArtifacts(PFUser $user) {
        $artifacts = $this->artifact->getUniqueLinkedArtifacts($user);
        $root_node = $this->getPlannedArtifacts();
        // TODO get rid of this if, in favor of an empty treenode
        if ($root_node) {
            $this->addChildrenNodes($root_node, $artifacts, $user);
        }
        return $artifacts;
    }

    private function addChildrenNodes(ArtifactNode $root_node, &$artifacts, $user) {
        foreach ($root_node->getChildren() as $node) {
            $artifact    = $node->getObject();
            $artifacts[] = $artifact;
            $artifacts   = array_merge($artifacts, $artifact->getUniqueLinkedArtifacts($user));
            $this->addChildrenNodes($node, $artifacts, $user);
        }
    }
    
    public function hasAncestors() {
        return !empty($this->parent_milestones);
    }

    public function getAncestors() {
        return $this->parent_milestones;
    }

    public function getParent() {
        return array_shift(array_values($this->parent_milestones));
    }

    public function setAncestors(array $parents) {
        $this->parent_milestones = $parents;
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

        $time_period = new TimePeriodWithoutWeekEnd($this->start_date, $this->duration);

        return $time_period->getEndDate();
    }

    public function getCapacity() {
        return $this->capacity;
    }

    public function setCapacity($capacity) {
        $this->capacity = $capacity;
        return $this;
    }

    /**
     * @param array $artifacts_ids
     * @param PFUser $user
     * @return Boolean True if nothing went wrong
     */
    public function solveInconsistencies(PFUser $user, array $artifacts_ids) {
        $success  = true;
        $artifact = $this->getArtifact();

        foreach ($artifacts_ids as $artifact_id){
            $success = $success && $artifact->linkArtifact($artifact_id, $user);
        }

        return $success;
    }

    /**
     * Get the timestamp of the last modification of the milestone
     *
     * @return Integer
     */
    public function getLastModifiedDate() {
        return $this->getArtifact()->getLastChangeset()->getSubmittedOn();
    }

    /**
     * @see Planning_Milestone::getDuration()
     * @return float
     */
    public function getDuration() {
        return $this->duration;
    }

}
?>
