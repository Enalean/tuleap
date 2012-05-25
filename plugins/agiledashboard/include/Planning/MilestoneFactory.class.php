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

require_once 'Milestone.class.php';
require_once 'NoMilestone.class.php';

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
     * Instanciates a new milestone factory.
     * 
     * @param PlanningFactory         $planning_factory The factory to delegate planning retrieval.
     * @param Tracker_ArtifactFactory $artifact_factory The factory to delegate artifacts retrieval.
     */
    public function __construct(PlanningFactory         $planning_factory,
                                Tracker_ArtifactFactory $artifact_factory) {
        
        $this->planning_factory = $planning_factory;
        $this->artifact_factory = $artifact_factory;
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
     * TODO: group_id should die.
     * 
     * @param User $user
     * @param int $group_id
     * @param int $planning_id
     * @param int $artifact_id
     * 
     * @return Planning_Milestone
     */
    public function getMilestoneWithPlannedArtifacts(User $user, $group_id, $planning_id, $artifact_id) {
        $planning = $this->planning_factory->getPlanningWithTrackers($planning_id);
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        
        if ($artifact) {
            $content_tree = $this->getPlannedArtifacts($user, $planning, $artifact, 1);
            $this->removeSubMilestones($user, $artifact, $content_tree);
            
            return new Planning_Milestone($group_id, $planning, $artifact, $content_tree);
        } else {
            return new Planning_NoMilestone($group_id, $planning);
        }
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
            $data = $node->getData();
            if (in_array($data['id'], $hierarchy_children_ids)) {
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
     * @param int              $child_depth
     * 
     * @return TreeNode
     */
    private function getPlannedArtifacts(User             $user,
                                         Planning         $planning,
                                         Tracker_Artifact $milestone_artifact,
                                                          $child_depth) {
        if ($milestone_artifact == null) return;
        
        $id               = $milestone_artifact->getId();
        $backlog_trackers = $planning->getBacklogTrackers();
        
        $node = new TreeNode(array('id'                   => $id,
                                   'allowedChildrenTypes' => $backlog_trackers));
        $node->setId($id);
        $this->addChildrenPlannedArtifacts($user, $milestone_artifact, $node, $child_depth);
        
        return $node;
    }
    
    /**
     * Adds $parent_node children according to $artifact ones.
     * 
     * @param type $user
     * @param type $artifact
     * @param type $parent_node
     * @param type $child_depth
     * 
     * @return boolean 
     */
    private function addChildrenPlannedArtifacts(User             $user,
                                                 Tracker_Artifact $artifact,
                                                 TreeNode         $parent_node,
                                                                  $child_depth = 0) {
        $linked_artifacts = $artifact->getUniqueLinkedArtifacts($user);
        if (! $linked_artifacts) return false;
        
        foreach ($linked_artifacts as $linked_artifact) {
            $artifact_node = new TreeNode(array('id' => $linked_artifact->getId()));
            $artifact_node->setId($linked_artifact->getId());
            
            if ($child_depth > 0) {
                $this->addChildrenPlannedArtifacts($user, $linked_artifact, $artifact_node, $child_depth - 1);
            }
            
            $parent_node->addChild($artifact_node);
        }
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
                    $sub_milestones[] = new Planning_Milestone($milestone->getGroupId(),
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
        return $milestone;
    }
}
?>
