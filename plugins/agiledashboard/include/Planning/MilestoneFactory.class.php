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
            
            return new Planning_Milestone($group_id, $planning, $artifact, $content_tree);
        } else {
            return new Planning_NoMilestone($group_id, $planning);
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
            $node = new TreeNode(array('id' => $linked_artifact->getId()));
            $node->setId($linked_artifact->getId());
            
            if ($child_depth > 0) {
                $this->addChildrenPlannedArtifacts($user, $linked_artifact, $node, $child_depth - 1);
            }
            
            $parent_node->addChild($node);
        }
    }
}
?>
