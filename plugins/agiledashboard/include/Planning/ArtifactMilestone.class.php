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
     * @var TreeNode
     */
    private $planned_artifacts;

    /**
     * A sub-milestone is a decomposition of the current one.
     *
     * Given current Milestone is a Release
     * And there is a Parent/Child association between Release and Sprint
     * Then $sub_milestone will be an array of sprint
     *
     * @var array of Planning_Milestone
     */
    private $sub_milestones = array();

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
     * The effort needed to complete the milestone. It's a numerical quantification
     * of the workload.
     *
     * @var Float
     */
    private $remaining_effort = null;

    /**
     * The estimated workforce of the team for given milestone.
     * It's set at the beginning of the Milestone and shall not change during its life.
     *
     * @var Float
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
                                TreeNode         $planned_artifacts = null) {

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
     * @return array of Planning_Milestone
     */
    public function getSubMilestones() {
        return $this->sub_milestones;
    }

    /**
     * @return Boolean True if milestone has at least 1 sub-milestone.
     */
    public function hasSubMilestones() {
        return ! empty($this->sub_milestones);
    }

    /**
     * Adds some sub-milestones. Ignores milestones which are already a
     * sub-milestone of the current one.
     *
     * @param array $new_sub_milestones
     */
    public function addSubMilestones(array $new_sub_milestones) {
        $this->sub_milestones = array_merge($this->sub_milestones, $new_sub_milestones);
    }

    /**
     * @return Boolean
     */
    public function userCanView(User $user) {
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
     * @return TreeNode
     */
    public function getPlannedArtifacts() {
        return $this->planned_artifacts;
    }

    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts()
     * @param User $user
     * @return Array of Tracker_Artifact
     */
    public function getLinkedArtifacts(User $user) {
        $artifacts = $this->artifact->getUniqueLinkedArtifacts($user);
        $root_node = $this->getPlannedArtifacts();
        // TODO get rid of this if, in favor of an empty treenode
        if ($root_node) {
            $this->addChildrenNodes($root_node, $artifacts, $user);
        }
        return $artifacts;
    }

    private function addChildrenNodes(TreeNode $root_node, &$artifacts, $user) {
        foreach ($root_node->getChildren() as $node) {
            $artifact    = $node->getObject();
            $artifacts[] = $artifact;
            $artifacts   = array_merge($artifacts, $artifact->getUniqueLinkedArtifacts($user));
            $this->addChildrenNodes($node, $artifacts, $user);
        }
    }

    public function getRemainingEffort() {
        return $this->remaining_effort;
    }

    /**
     * @param float $remaining_effort
     *
     * @return Planning_ArtifactMilestone
     */
    public function setRemainingEffort($remaining_effort) {
        $this->remaining_effort = $remaining_effort;
        return $this;
    }

    public function getCapacity() {
        return $this->capacity;
    }

    /**
     * @param float $capacity
     *
     * @return Planning_ArtifactMilestone
     */
    public function setCapacity($capacity) {
        $this->capacity = $capacity;
        return $this;
    }

    public function hasAncestors() {
        return !empty($this->parent_milestones);
    }

    public function getAncestors() {
        return $this->parent_milestones;
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
}

?>
