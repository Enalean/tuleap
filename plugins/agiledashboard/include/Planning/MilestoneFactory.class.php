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
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * Instanciates a new milestone factory.
     *
     * @param PlanningFactory            $planning_factory    The factory to delegate planning retrieval.
     * @param Tracker_ArtifactFactory    $artifact_factory    The factory to delegate artifacts retrieval.
     * @param Tracker_FormElementFactory $formelement_factory The factory to delegate artifacts retrieval.
     */
    public function __construct(
        PlanningFactory $planning_factory,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $formelement_factory,
        TrackerFactory $tracker_factory
    ) {

        $this->planning_factory    = $planning_factory;
        $this->artifact_factory    = $artifact_factory;
        $this->formelement_factory = $formelement_factory;
        $this->tracker_factory     = $tracker_factory;
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
    public function getLastOpenMilestones(PFUser $user, Planning $planning, $number_to_fetch) {
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

    private function getLastOpenArtifacts(PFUser $user, Planning $planning, $number_to_fetch) {
        $artifacts  = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        ksort($artifacts);
        return array_slice($artifacts, - $number_to_fetch);
    }

    private function getPlannedArtifactsForLatestMilestone(PFUser $user, Tracker_Artifact $artifact, $current_index, $number_of_artifacts) {
        if ($current_index >= $number_of_artifacts) {
            return $this->getPlannedArtifacts($user, $artifact);
        }
    }

    /**
     * A Bare Milestone is a milestone with minimal information to display (ie. without planned artifacts).
     *
     * It would deserve a dedicated object but it's a bit complex to setup today due to
     * MilestoneController::getAlreadyPlannedArtifacts()
     *
     * Only objects that should be visible for the given user are loaded.
     *
     * @param PFUser $user
     * @param Project $project
     * @param Integer $planning_id
     * @param Integer $artifact_id
     * 
     * @return Planning_Milestone
     * @throws Planning_NoPlanningsException
     */
    public function getBareMilestone(PFUser $user, Project $project, $planning_id, $artifact_id) {
        $planning = $this->planning_factory->getPlanningWithTrackers($planning_id);
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);

        if ($artifact && $artifact->userCanView($user)) {
            $milestone = new Planning_ArtifactMilestone($project, $planning, $artifact);
            $milestone->setAncestors($this->getMilestoneAncestors($user, $milestone));
            $this->updateMilestoneContextualInfo($user, $milestone);
            return $milestone;
        } else {
            return new Planning_NoMilestone($project, $planning);
        }
    }

    /**
     * Build a fake milestone that catch all submilestones of root planning
     *
     * @param PFUser $user
     * @param Project $project
     *
     * @return Planning_VirtualTopMilestone
     */
    public function getVirtualTopMilestone(PFUser $user, Project $project) {
        return new Planning_VirtualTopMilestone(
            $project,
            $this->planning_factory->getVirtualTopPlanning($user, $project->getID())
        );
    }

    public function updateMilestoneContextualInfo(PFUser $user, Planning_Milestone $milestone) {
        return $milestone
            ->setStartDate($this->getTimestamp($user, $milestone, Planning_Milestone::START_DATE_FIELD_NAME))
            ->setDuration($this->getComputedFieldValue($user, $milestone, Planning_Milestone::DURATION_FIELD_NAME));
    }

    private function getTimestamp(PFUser $user, Planning_Milestone $milestone, $field_name) {
        $milestone_artifact = $milestone->getArtifact();
        $field = $this->formelement_factory->getUsedFieldByNameForUser($milestone_artifact->getTracker()->getId(), $field_name, $user);

        if (! $field) {
            return 0;
        }

        $value = $field->getLastChangesetValue($milestone_artifact);
        if (! $value) {
            return 0;
        }

        return $value->getTimestamp();
    }

    protected function getComputedFieldValue(PFUser $user, Planning_Milestone $milestone, $field_name) {
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
     * Add planned artifacts to Planning_Milestone
     *
     * Only objects that should be visible for the given user are loaded.
     *
     * @param PFUser $user
     *
     */
    public function updateMilestoneWithPlannedArtifacts(PFUser $user, Planning_Milestone $milestone) {
        $planned_artifacts = $this->getPlannedArtifacts($user, $milestone->getArtifact());
        $this->removeSubMilestones($user, $milestone->getArtifact(), $planned_artifacts);

        $milestone->setPlannedArtifacts($planned_artifacts);
    }

    /**
     * Return a MilestonePlan object properly configured
     *
     * @param PFUser               $user
     * @param Planning_Milestone $milestone
     *
     * @return Planning_MilestonePlan
     */
    public function getMilestonePlan(PFUser $user, Planning_Milestone $milestone) {
        $this->updateMilestoneWithPlannedArtifacts($user, $milestone);
        return new Planning_MilestonePlan(
            $milestone,
            $this->getSubMilestones($user, $milestone),
            $this->getComputedFieldValue($user, $milestone, Planning_Milestone::CAPACITY_FIELD_NAME),
            $this->getComputedFieldValue($user, $milestone, Planning_Milestone::REMAINING_EFFORT_FIELD_NAME)
        );
    }

    /**
     * Removes the sub-milestone artifacts from an artifacts tree.
     *
     * @param PFUser             $user               The user accessing the data
     * @param Tracker_Artifact $milestone_artifact The parent artifact of sub-milestones artifacts
     * @param TreeNode         $artifacts_tree     The artifacts tree to clean up
     */
    private function removeSubMilestones(PFUser $user, Tracker_Artifact $milestone_artifact, TreeNode $artifacts_tree) {
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
     * @param PFUser             $user
     * @param Planning         $planning
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return TreeNode
     */
    public function getPlannedArtifacts(PFUser             $user,
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
    private function addChildrenPlannedArtifacts(PFUser             $user,
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
    public function getSubMilestones(PFUser $user, Planning_Milestone $milestone) {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            return $this->getTopSubMilestones($user, $milestone);
        } else {
            return $this->getRegularSubMilestones($user, $milestone);
        }
    }

    private function getRegularSubMilestones(PFUser $user, Planning_Milestone $milestone) {
        $milestone_artifact = $milestone->getArtifact();
        $sub_milestones     = array();

        if ($milestone_artifact) {
            foreach($this->getSubMilestonesArtifacts($user, $milestone_artifact) as $sub_milestone_artifact) {
                $planning = $this->planning_factory->getPlanningByPlanningTracker($sub_milestone_artifact->getTracker());

                if ($planning) {
                    $sub_milestones[] = new Planning_ArtifactMilestone(
                        $milestone->getProject(),
                        $planning,
                        $sub_milestone_artifact
                    );
                }
            }
        }

        return $sub_milestones;
    }

    /**
     * Return the list of top most milestones
     *
     * @param PFUser $user
     * @param Planning_VirtualTopMilestone $top_milestone
     *
     * @return Planning_ArtifactMilestone[]
     */
    private function getTopSubMilestones(PFUser $user, Planning_VirtualTopMilestone $top_milestone) {
        $milestones = array();
        if (! $top_milestone->getPlanning()) {
            return $milestones;
        }

        $root_planning = $this->planning_factory->getRootPlanning($user, $top_milestone->getProject()->getID());
        $milestone_planning_tracker_id = $top_milestone->getPlanning()->getPlanningTrackerId();
        $artifacts = $this->artifact_factory->getArtifactsByTrackerId($milestone_planning_tracker_id);

        if ($milestone_planning_tracker_id) {
            foreach($artifacts as $artifact) {
                if ($artifact->getLastChangeset() && $artifact->userCanView($user)) {
                    $milestones[] = new Planning_ArtifactMilestone(
                        $top_milestone->getProject(),
                        $root_planning,
                        $artifact
                    );
                }
            }
        }

        return $milestones;
    }

    /**
     * Retrieves the sub-milestones of a given parent milestone artifact.
     *
     * @param PFUser             $user
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return array of Tracker_Artifact
     */
    private function getSubMilestonesArtifacts(PFUser $user, Tracker_Artifact $milestone_artifact) {
        return array_values($milestone_artifact->getHierarchyLinkedArtifacts($user));
    }

    /**
     * Retrieves the sub-milestones aids of a given parent milestone artifact.
     *
     * @param PFUser             $user
     * @param Tracker_Artifact $milestone_artifact
     *
     * @return array of int
     */
    private function getSubMilestonesArtifactIds(PFUser $user, Tracker_Artifact $milestone_artifact) {
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
     * Return all open milestone without their content
     *
     * @param PFUser $user
     * @param Planning $planning
     * @return Planning_ArtifactMilestone[]
     */
    public function getAllBareMilestones(PFUser $user, Planning $planning) {
        $milestones = array();
        $project    = $planning->getPlanningTracker()->getProject();
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        foreach ($artifacts as $artifact) {
            $milestones[] = new Planning_ArtifactMilestone($project, $planning, $artifact);
        }
        return $milestones;
    }

    /**
     * Loads all open milestones for the given project and planning
     *
     * @param PFUser $user
     * @param Project $project
     * @param Planning $planning
     *
     * @return Array of \Planning_Milestone
     */
    public function getAllMilestones(PFUser $user, Planning $planning) {
        if (! isset($this->cache_all_milestone[$planning->getId()])) {
            $this->cache_all_milestone[$planning->getId()] = $this->getAllMilestonesWithoutCaching($user, $planning);
        }
        return $this->cache_all_milestone[$planning->getId()];
    }

    private function getAllMilestonesWithoutCaching(PFUser $user, Planning $planning) {
        $project = $planning->getPlanningTracker()->getProject();
        $milestones = array();
        $artifacts  = $this->artifact_factory->getArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        foreach ($artifacts as $artifact) {
            /** @todo: this test is only here if we have crappy data in the db
             * ie. an artifact creation failure that leads to an incomplete artifact.
             * this should be fixed in artifact creation (transaction & co) and after
             * DB clean, the following test can be removed.
             */
            if ($artifact->getLastChangeset()) {
                $planned_artifacts = $this->getPlannedArtifacts($user, $artifact);
                $milestones[]      = new Planning_ArtifactMilestone($project, $planning, $artifact, $planned_artifacts);
            }
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
    public function getMilestoneFromArtifactWithPlannedArtifacts(Tracker_Artifact $artifact, PFUser $user) {
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
        if ( ! $planning) {
            return null;
        }

        return new Planning_ArtifactMilestone($tracker->getProject(), $planning, $artifact, $planned_artifacts);
    }

    /**
     * Returns an array with all Parent milestone of given milestone.
     *
     * The array starts with current milestone, until the "oldest" ancestor
     * 0 => Sprint, 1 => Release, 2=> Product
     *
     * @param PFUser               $user
     * @param Planning_Milestone $milestone
     *
     * @return Array of Planning_Milestone
     */
    public function getMilestoneAncestors(PFUser $user, Planning_Milestone $milestone) {
        $parent_milestone   = array();
        $milestone_artifact = $milestone->getArtifact();
        if ($milestone_artifact) {
            $parent_artifacts = $milestone_artifact->getAllAncestors($user);
            foreach ($parent_artifacts as $artifact) {
                $parent_milestone[] = $this->getMilestoneFromArtifact($artifact);
            }
        }
        $parent_milestone = array_filter($parent_milestone);
        return $parent_milestone;
    }

    /**
     * Get all milestones that share the same parent than given milestone.
     *
     * @param PFUser $user
     * @param Planning_Milestone $milestone
     *
     * @return Array of Planning_Milestone
     */
    public function getSiblingMilestones(PFUser $user, Planning_Milestone $milestone) {
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
     * @param PFUser    $user
     * @param Integer $planning_id
     *
     * @return Planning_Milestone
     */
    public function getCurrentMilestone(PFUser $user, $planning_id) {
        
        $planning  = $this->planning_factory->getPlanningWithTrackers($planning_id);
        $artifacts = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($user, $planning->getPlanningTrackerId());
        if (count($artifacts) > 0) {
            return $this->getMilestoneFromArtifact(array_shift($artifacts));
        }
        return new Planning_NoMilestone($planning->getPlanningTracker()->getProject(), $planning);
    }
}

?>
