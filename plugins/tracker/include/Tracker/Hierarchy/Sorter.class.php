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
 * Sorts artifacts in a TreeNode structure 
 */
class Tracker_Hierarchy_Sorter {
    
    /**
     * The artifacts tree matches the trackers hierarchy definition.
     * 
     * @param DataAccessResult $artifacts
     * @param array $tracker_ids
     * @param Tracker_Hierarchy $hierarchy
     * @return \TreeNode 
     */
    public function sortArtifacts($artifacts, array $tracker_ids, Tracker_Hierarchy $hierarchy) {
        $root = new TreeNode();
        $root->setId(0);
        if ($artifacts) {
            list($artifacts_by_id, $artifacts_by_tracker) = $this->indexArtifactsByIdAndTracker($artifacts);
            $tracker_ids = $hierarchy->sortTrackerIds($tracker_ids);
            $this->organizeArtifactsInTrackerHierarchy($root, $hierarchy, $artifacts_by_id, $artifacts_by_tracker, $tracker_ids);
        }
        return $root;
    }
    
    private function organizeArtifactsInTrackerHierarchy($parent, $hierarchy, $artifacts_by_id, $artifacts_by_tracker, $tracker_ids) {
        $artifacts_in_tree = array();
        foreach ($tracker_ids as $tracker_id) {
            $this->appendArtifactsOfTracker($parent, $hierarchy, $artifacts_by_id, $artifacts_by_tracker, $tracker_id, $artifacts_in_tree);
        }
    }

    private function appendArtifactsOfTracker($parent, $hierarchy, $artifacts_by_id, $artifacts_by_tracker, $tracker_id, array &$artifacts_in_tree) {
        if (isset($artifacts_by_tracker[$tracker_id])) {
            foreach ($artifacts_by_tracker[$tracker_id] as $artifact) {
                $this->appendArtifactAndSonsToParent($parent, $hierarchy, $artifacts_by_id, $artifact, $artifacts_in_tree);
            }
        }
    }
    
    private function appendArtifactAndSonsToParent(TreeNode $parent, Tracker_Hierarchy $hierarchy, array $artifacts, array $artifact, array &$artifacts_in_tree) {
        $id = $artifact['id'];
        
        if (!isset($artifacts_in_tree[$id])) {
            $node = new TreeNode();
            
            $node->setId($id);
            $node->setData($artifact);
            $parent->addChild($node);
            
            $artifacts_in_tree[$id] = true;
            $artifactlinks          = explode(',', $artifact['artifactlinks']);
            
            foreach ($artifactlinks as $link_id) {
                if ($this->artifactCanBeAppended($link_id, $artifacts, $artifact, $hierarchy)) {
                    $this->appendArtifactAndSonsToParent($node, $hierarchy, $artifacts, $artifacts[$link_id], $artifacts_in_tree);
                }
            }
        }
    }
    
    private function artifactCanBeAppended($artifact_id, array $artifacts, array $parent_artifact, Tracker_Hierarchy $hierarchy) {
        return isset($artifacts[$artifact_id]) && $hierarchy->isChild($parent_artifact['tracker_id'], $artifacts[$artifact_id]['tracker_id']);
    }
    
    private function indexArtifactsByIdAndTracker($artifacts) {
        $artifactsById        = array();
        $artifacts_by_tracker = array();
        
        foreach ($artifacts as $artifact) {
            //by id
            $artifactsById[$artifact['id']] = $artifact;
            
            //by tracker_id
            $tracker_id = $artifact['tracker_id'];
            if (isset($artifacts_by_tracker[$tracker_id])) {
                $artifacts_by_tracker[$tracker_id][] = $artifact;
            } else {
                $artifacts_by_tracker[$tracker_id] = array($artifact);
            }
        }
        return array($artifactsById, $artifacts_by_tracker);
    }
    
}
?>
