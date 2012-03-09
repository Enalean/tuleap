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

class Tracker_Hierarchy_HierarchicalTrackerFactory {

    public function __construct(TrackerFactory $tracker_factory, Tracker_Hierarchy_Dao $dao) {
        $this->tracker_factory = $tracker_factory;
        $this->dao             = $dao;
    }
    
    /**
     * @return Tracker_Hierarchy_HierarchicalTracker
     */
    public function getWithChildren(Tracker $tracker) {
        $dar      = $this->dao->getChildren($tracker->getId());
        $children = array();
        
        foreach($dar as $row) {
            $children[] = $this->tracker_factory->getTrackerById($row['child_id']);
        }
        
        return new Tracker_Hierarchy_HierarchicalTracker($tracker, $children);
    }
    
    /**
     * @return Array of Tracker
     */
    public function getPossibleChildren(Tracker_Hierarchy_HierarchicalTracker $tracker) {
        $project_id        = $tracker->getProject()->getId();
        $project_trackers  = $this->tracker_factory->getTrackersByGroupId($project_id);
        $ids_to_remove     = $this->dao->getAncestorIds($tracker->getId());
        $ids_to_remove[]   = $tracker->getId();
        
        $project_trackers = $this->removeIdsFromTrackerList($project_trackers, $ids_to_remove);
        
        return $project_trackers;
    }
    
    private function removeIdsFromTrackerList($tracker_list, $tracker_ids_to_remove) {
        $array_with_keys_to_remove = array_combine($tracker_ids_to_remove, range(0, count($tracker_ids_to_remove)-1));
        return array_diff_key($tracker_list, $array_with_keys_to_remove);
        
    }
    
    /**
     * @return TreeNode
     */
    public function getHierarchy(Tracker $tracker) {
        $project_trackers = $this->tracker_factory->getTrackersByGroupId($tracker->getGroupId());
        $hierarchy_dar    = $this->dao->getHierarchy($tracker->getGroupId());
        $root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $tracker->getId());
        $hierarchy = new TreeNode();
        $hierarchy->setId(0);
        
        $root_tracker_node = $this->makeNodeFor($project_trackers[$root_tracker_id]);
        foreach ($this->buildHierarchyChildrenOf($root_tracker_id , iterator_to_array($hierarchy_dar), $project_trackers) as $child) {
            $root_tracker_node->addChild($child);
        }
        $hierarchy->addChild($root_tracker_node);
        return $hierarchy;
    }
    
    private function buildHierarchyChildrenOf($parent_id, $hierarchy_dar, $project_trackers) {
        $children = array();
        foreach($hierarchy_dar as $row) {
            if ($row['parent_id'] == $parent_id) {
                $id      = $row['child_id'];
                $node = $this->makeNodeFor($project_trackers[$id]);

                foreach ($this->buildHierarchyChildrenOf($id, $hierarchy_dar, $project_trackers) as $child) {
                    $node->addChild($child);
                }
                $children[] = $node;
            }
        }
        return $children;
    }
    
    public function getRootTrackerId($hierarchy_dar, $current_tracker_id) {
        foreach($hierarchy_dar as $child) {
            if ($child['child_id'] == $current_tracker_id) {
                return $this->getRootTrackerId($hierarchy_dar, $child['parent_id']);
            }
        }
        return $current_tracker_id;
    }

    public function makeNodeFor($tracker) {
        $node = new TreeNode(
            array('name'     => $tracker->getName(),
                  'id'       => $tracker->getId()
            )
        );
        $node->setId($tracker->getId());
        return $node;
    }
}

?>
