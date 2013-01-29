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

require_once dirname(__FILE__).'/../../../tracker/include/Tracker/CrossSearch/ArtifactNode.class.php';

/**
 * Loads planning milestones from the persistence layer.
 */
class Planning_MilestoneFactory {

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * Instanciates a new milestone factory.
     *
     * @param PlanningFactory            $planning_factory    The factory to delegate planning retrieval.
     * @param Tracker_ArtifactFactory    $artifact_factory    The factory to delegate artifacts retrieval.
     * @param Tracker_FormElementFactory $formelement_factory The factory to delegate artifacts retrieval.
     */
    public function __construct(PlanningFactory            $planning_factory,
                                Tracker_ArtifactFactory    $artifact_factory,
                                Tracker_FormElementFactory $formelement_factory) {

        $this->planning_factory    = $planning_factory;
        $this->artifact_factory    = $artifact_factory;
        $this->formelement_factory = $formelement_factory;
    }

    /**
     * Return an empty milestone for given planning/project.
     *
     * @param Project $project
     * @param Integer $planning_id
     *
     * @return Planning_NoMilestone
     */
    public function getNoMilestone(Project $project, $planning_id) {
        $planning = $this->planning_factory->getPlanningWithTrackers($planning_id);
        return new Planning_NoMilestone($project, $planning);
    }

    /**
     * @return array of Planning_Milestone (the last $number_to_fetch open ones for the given $planning)
     */
    public function getLastOpenMilestones(User $user, Planning $planning, $number_to_fetch) {
        $artifacts           = $this->getLastOpenArtifacts($user, $planning, $number_to_fetch);
        $number_of_artifacts = count($artifacts);
        $current_index       = 0;
        $milestones          = array();
        foreach ($artifacts as $artifact) {
            $planned_artifacts = $this->getPlannedArtifactsForLatestMilestone($user, $artifact, ++$current_index, $number_of_artifacts);
            $milestones[] = $this->getMilestoneFromArtifact($artifact, $planned_artifacts);
        }
        return $milestones;
    }

    private function getLastOpenArtifacts(User $user, Planning $planning, $number_to_fetch) {
        $artifacts  = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        ksort($artifacts);
        return array_slice($artifacts, - $number_to_fetch);
    }

    private function getPlannedArtifactsForLatestMilestone(User $user, Tracker_Artifact $artifact, $current_index, $number_of_artifacts) {
        if ($current_index >= $number_of_artifacts) {
            return $this->getPlannedArtifacts($user, $artifact);
        }
    }

    /**
     * Loads the milestone matching the given planning and artifact ids.
     *
     * Also loads:
     *   - the planning this milestone belongs to
     *   - the planning tracker and the backlog trackers of this planning
     *   - the artifacts planned for this milestone
     *
     * Only objects that should be visible for the given user are loaded.
     *
     * @param User $user
     * @param Project $project
     * @param int $planning_id
     * @param int $artifact_id
     *
     * @return Planning_Milestone
     */
    public function getMilestoneWithPlannedArtifacts(User $user, Project $project, $planning_id, $artifact_id) {
        $planning = $this->planning_factory->getPlanningWithTrackers($planning_id);
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);

        if ($artifact) {
            $planned_artifacts = $this->getPlannedArtifacts($user, $artifact);
            $this->removeSubMilestones($user, $artifact, $planned_artifacts);

            $milestone = new Planning_ArtifactMilestone($project, $planning, $artifact, $planned_artifacts);
            return $this->updateMilestoneContextualInfo($user, $milestone);
        } else {
            return new Planning_NoMilestone($project, $planning);
        }
    }

    private function updateMilestoneContextualInfo(User $user, Planning_ArtifactMilestone $milestone) {
        return $milestone
            ->setCapacity($this->getFieldValue($user, $milestone, Planning_Milestone::CAPACITY_FIELD_NAME))
            ->setRemainingEffort($this->getFieldValue($user, $milestone, Planning_Milestone::REMAINING_EFFORT_FIELD_NAME))
            ->setStartDate($this->getDateFieldValue($milestone, Planning_Milestone::START_DATE_FIELD_NAME));
    }

    private function getDateFieldValue($milestone, $field_name) {
        $milestone_artifact = $milestone->getArtifact();
        $field              = $this->formelement_factory->getFormElementByName($milestone_artifact->getTracker()->getId(), $field_name);

        if ($field) {
            return $field->getLastValue($milestone_artifact);
        }
        return 0;
    }

    private function getFieldValue(User $user, Planning_ArtifactMilestone $milestone, $field_name) {
        $milestone_artifact = $milestone->getArtifact();
        $field = $this->formelement_factory->getComputableFieldByNameForUser(
            $milestone_artifact->getTracker()->getId(),
            $field_name,
            $user
        );
        if ($field) {
            return $field->getComputedValue($user, $milestone_artifact);
        }
        return 0;
    }

    /**
     * Removes the sub-milestone artifacts from an artifacts tree.
     *
     * @param User             $user               The user accessing the data
     * @param Tracker_Artifact $milestone_artifact The parent artifact of sub-milestones artifacts
     * @param TreeNode         $artifacts_tree     The artifacts tree to clean up
     */
    private function removeSubMilestones(User $user, Tracker_Artifact $milestone_artifact, TreeNode $artifacts_tree) {
        $hierarchy_children_ids = $this->getSubMilestonesArtifactIds($user, $milestone_artifact);

        foreach ($artifacts_tree->getChildren() as $node) {
            if (in_array($node->getId(), $hierarchy_children_ids)) {
                $artifacts_tree->removeChild(null, $node);
            }
        }
    }

    /**
     * Retrieves the artifacts planned for the given milestone artifact.
     *
     * @param User             $user
     * @param Planning         $planning
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return TreeNode
     */
    public function getPlannedArtifacts(User             $user,
                                        Tracker_Artifact $milestone_artifact) {
        if ($milestone_artifact == null) return; //it is not possible!

        $parents = array();
        $node    = $this->makeNodeWithChildren($user, $milestone_artifact, $parents);

        return $node;
    }

    /**
     * Adds $parent_node children according to $artifact ones.
     *
     * @param type $user
     * @param type $artifact
     * @param type $parent_node
     * @param type $parents     The list of parents to prevent infinite recursion
     *
     * @return boolean
     */
    private function addChildrenPlannedArtifacts(User             $user,
                                                 Tracker_Artifact $artifact,
                                                 TreeNode         $parent_node,
                                                 array            $parents) {
        $linked_artifacts = $artifact->getUniqueLinkedArtifacts($user);
        if (! $linked_artifacts) return false;
        if (in_array($artifact->getId(), $parents)) return false;

        $parents[] = $artifact->getId();
        foreach ($linked_artifacts as $linked_artifact) {
            $node = $this->makeNodeWithChildren($user, $linked_artifact, $parents);
            $parent_node->addChild($node);
        }
    }

    private function makeNodeWithChildren($user, $artifact, $parents) {
        $node = new ArtifactNode($artifact);
        $this->addChildrenPlannedArtifacts($user, $artifact, $node, $parents);
        return $node;
    }

    /**
     * Retrieve the sub-milestones of the given milestone.
     *
     * @param Planning_Milestone $milestone
     *
     * @return array of Planning_Milestone
     */
    public function getSubMilestones(User $user, Planning_Milestone $milestone) {
        $milestone_artifact = $milestone->getArtifact();
        $sub_milestones     = array();

        if ($milestone_artifact) {
            foreach($this->getSubMilestonesArtifacts($user, $milestone_artifact) as $sub_milestone_artifact) {
                $planning = $this->planning_factory->getPlanningByPlanningTracker($sub_milestone_artifact->getTracker());

                if ($planning) {
                    $sub_milestones[] = new Planning_ArtifactMilestone($milestone->getProject(),
                                                               $planning,
                                                               $sub_milestone_artifact);
                }
            }
        }

        return $sub_milestones;
    }

    /**
     * Retrieves the sub-milestones of a given parent milestone artifact.
     *
     * @param User             $user
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return array of Tracker_Artifact
     */
    private function getSubMilestonesArtifacts(User $user, Tracker_Artifact $milestone_artifact) {
        return array_values($milestone_artifact->getHierarchyLinkedArtifacts($user));
    }

    /**
     * Retrieves the sub-milestones aids of a given parent milestone artifact.
     *
     * @param User             $user
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return array of int
     */
    private function getSubMilestonesArtifactIds(User $user, Tracker_Artifact $milestone_artifact) {
        return array_map(array($this, 'getArtifactId'),
                         $this->getSubMilestonesArtifacts($user, $milestone_artifact));
    }

    /**
     * TODO: Make it a Tracker_Artifact static method ?
     *
     * @param Tracker_Artifact $artifact
     *
     * @return int
     */
    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
    }

    /**
     * Loads the milestone matching the given planning and artifact ids.
     *
     * Also loads:
     *   - the planning this milestone belongs to
     *   - the planning tracker and the backlog trackers of this planning
     *   - the artifacts planned for this milestone
     *   - the sub-milestones
     *
     * @param User $user
     * @param int  $group_id
     * @param int  $planning_id
     * @param int  $artifact_id
     *
     * @return Planning_Milestone
     */
    public function getMilestoneWithPlannedArtifactsAndSubMilestones(User $user, $group_id, $planning_id, $artifact_id) {
        $milestone = $this->getMilestoneWithPlannedArtifacts($user, $group_id, $planning_id, $artifact_id);
        $milestone->addSubMilestones($this->getSubMilestones($user, $milestone));
        $milestone->setAncestors($this->getMilestoneAncestors($user, $milestone));
        return $milestone;
    }

    /**
     * Loads all open milestones for the given project and planning
     *
     * @param User $user
     * @param Project $project
     * @param Planning $planning
     *
     * @return Array of \Planning_Milestone
     */
    public function getAllMilestones(User $user, Planning $planning) {
        $project = $planning->getPlanningTracker()->getProject();
        $milestones = array();
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        foreach ($artifacts as $artifact) {
            $planned_artifacts = $this->getPlannedArtifacts($user, $artifact);
            $milestones[]      = new Planning_ArtifactMilestone($project, $planning, $artifact, $planned_artifacts);
        }
        return $milestones;
    }

    /**
     * Create a Milestone corresponding to given artifact and loads the artifacts planned for this milestone
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Planning_ArtifactMilestone
     */
    public function getMilestoneFromArtifactWithPlannedArtifacts(Tracker_Artifact $artifact, User $user) {
        $planned_artifacts = $this->getPlannedArtifacts($user, $artifact);
        return $this->getMilestoneFromArtifact($artifact, $planned_artifacts);
    }

    /**
     * Create a Milestone corresponding to given artifact
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Planning_ArtifactMilestone
     */
    public function getMilestoneFromArtifact(Tracker_Artifact $artifact, TreeNode $planned_artifacts = null) {
        $tracker  = $artifact->getTracker();
        $planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);
        return new Planning_ArtifactMilestone($tracker->getProject(), $planning, $artifact, $planned_artifacts);
    }

    /**
     * Returns an array with all Parent milestone of given milestone.
     *
     * The array starts with current milestone, until the "oldest" ancestor
     * 0 => Sprint, 1 => Release, 2=> Product
     *
     * @param User               $user
     * @param Planning_Milestone $milestone
     *
     * @return Array of Planning_Milestone
     */
    public function getMilestoneAncestors(User $user, Planning_Milestone $milestone) {
        $parent_milestone   = array();
        $milestone_artifact = $milestone->getArtifact();
        if ($milestone_artifact) {
            $parent_artifacts = $milestone_artifact->getAllAncestors($user);
            foreach ($parent_artifacts as $artifact) {
                $parent_milestone[] = $this->getMilestoneFromArtifact($artifact);
            }
        }
        return $parent_milestone;
    }

    /**
     * Get all milestones that share the same parent than given milestone.
     *
     * @param User $user
     * @param Planning_Milestone $milestone
     *
     * @return Array of Planning_Milestone
     */
    public function getSiblingMilestones(User $user, Planning_Milestone $milestone) {
        $sibling_milestones = array();
        $milestone_artifact = $milestone->getArtifact();
        if ($milestone_artifact) {
            foreach($milestone_artifact->getSiblings($user) as $sibling) {
                if ($sibling->getId() == $milestone_artifact->getId()) {
                    $sibling_milestones[] = $milestone;
                } else {
                    $sibling_milestones[] = $this->getMilestoneFromArtifact($sibling);
                }
            }
        }
        return $sibling_milestones;
    }

    /**
     * Get the top most recent milestone (last created artifact in planning tracker)
     *
     * @param User    $user
     * @param Integer $planning_id
     *
     * @return Planning_Milestone
     */
    public function getCurrentMilestone(User $user, $planning_id) {
        $planning  = $this->planning_factory->getPlanningWithTrackers($planning_id);
        $artifacts = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        if (count($artifacts) > 0) {
            return $this->getMilestoneFromArtifact(array_shift($artifacts));
        }
        return new Planning_NoMilestone($planning->getPlanningTracker()->getProject(), $planning);
    }
}
?>
