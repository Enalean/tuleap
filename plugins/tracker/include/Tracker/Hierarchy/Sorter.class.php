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

require_once 'Hierarchy.class.php';
require_once dirname(__FILE__).'/../CrossSearch/ArtifactNode.class.php';

/**
 * Sorts artifacts in a TreeNode structure 
 */

class Tracker_Hierarchy_Sorter {
    
    /**
     *
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    
    
    public function __construct(Tracker_ArtifactFactory $factory = null) {
        $this->artifact_factory = isset($factory) ? $factory : Tracker_ArtifactFactory::instance();
    }
    
    /**
     * Create a tree from the given list according to the hierarchy definition
     * 
     * @param DataAccessResult $artifacts
     * @param array $tracker_ids
     * @param Tracker_Hierarchy $hierarchy
     * 
     * @return \TreeNode 
     */
    public function buildTreeWithCompleteList($artifacts, array $tracker_ids, Tracker_Hierarchy $hierarchy) {
        $root = new TreeNode();
        $root->setId(0);
        if ($artifacts) {
            list($artifacts_by_id, $artifacts_by_tracker) = $this->indexArtifactsByIdAndTracker($artifacts);
            $tracker_ids = $hierarchy->sortTrackerIds($tracker_ids);
            $this->organizeArtifactsInTrackerHierarchy($root, $hierarchy, $artifacts_by_id, $artifacts_by_tracker, $tracker_ids);
        }
        return $root;
    }
    
    private function organizeArtifactsInTrackerHierarchy(TreeNode $parent, Tracker_Hierarchy $hierarchy, array $artifacts_by_id, array $artifacts_by_tracker, $tracker_ids) {
        $artifacts_in_tree = array();
        foreach ($tracker_ids as $tracker_id) {
            $this->appendArtifactsOfTracker($parent, $hierarchy, $artifacts_by_id, $artifacts_by_tracker, $tracker_id, $artifacts_in_tree);
        }
    }

    private function appendArtifactsOfTracker(TreeNode $parent, Tracker_Hierarchy $hierarchy, array $artifacts_by_id, array $artifacts_by_tracker, $tracker_id, array &$artifacts_in_tree) {
        if (isset($artifacts_by_tracker[$tracker_id])) {
            foreach ($artifacts_by_tracker[$tracker_id] as $artifact) {
                $this->appendArtifactAndSonsToParent($parent, $hierarchy, $artifacts_by_id, $artifact, $artifacts_in_tree);
            }
        }
    }
    
    private function appendArtifactAndSonsToParent(TreeNode $parent, Tracker_Hierarchy $hierarchy, array $artifacts, array $artifact_info, array &$artifacts_in_tree) {
        $id = $artifact_info['id'];
        
        if (!isset($artifacts_in_tree[$id])) {
            $artifact = $this->artifact_factory->getArtifactById($id);
            $node = new ArtifactNode($artifact, $artifact_info);
            $parent->addChild($node);
            
            $artifacts_in_tree[$id] = true;
            $artifactlinks          = explode(',', $artifact_info['artifactlinks']);
            
            foreach ($artifactlinks as $link_id) {
                if ($this->artifactCanBeAppended($link_id, $artifacts, $artifact_info, $hierarchy)) {
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

    /**
     * Given a partial result of search, re-add all descendants of retrieved artifacts (if any).
     *
     * @todo: limit to the hierarchy ? (currently add all the descendants)
     * 
     * @param User             $user
     * @param DataAccessResult $artifacts_info
     * @return \TreeNode
     */
    public function buildTreeWithMissingChildren(User $user, $artifacts_info, array $excluded_artifact_ids) {
        $root           = new TreeNode();
        $artifacts_info = $this->indexArtifactInfoByArtifactId($artifacts_info);
        $artifacts      = $this->getArtifactsFromArtifactInfo($artifacts_info);
        $artifacts_done = array();
        $this->buildArtifactsTree($user, $root, $artifacts, $artifacts_info, array_flip($excluded_artifact_ids), $artifacts_done);
        return $root;
    }

    private function indexArtifactInfoByArtifactId($artifacts_info) {
        $new_info = array();
        foreach ($artifacts_info as $artifact_info) {
            $new_info[$artifact_info['id']] = $artifact_info;
        }
        return $new_info;
    }

    private function getArtifactsFromArtifactInfo($artifacts_info) {
        $artifacts = array();
        foreach ($artifacts_info as $artifact_info) {
            $artifacts[] = $this->artifact_factory->getArtifactById($artifact_info['id']);
        }
        return $artifacts;
    }
    /**
     *
     * @param User     $user                  the user who build the tree
     * @param TreeNode $root                  the artifacts tree
     * @param array    $artifacts             list of artifacts
     * @param array    $artifacts_info        list of the artifacts informations : id, last_changeset_id, title, tracker_id, artifactlinks
     * @param array    $excluded_artifact_ids list of excluded artifact ids
     * @param array    $artifacts_done        list of artifacts already processed
     */
    private function buildArtifactsTree(User $user, TreeNode $root, array $artifacts, array $artifacts_info, array $excluded_artifact_ids, array &$artifacts_done) {
        var_dump($artifacts_info);
        foreach ($artifacts as $artifact) {
            $artifact_id = $artifact->getId();
            if (!isset($excluded_artifact_ids[$artifact_id]) && ! isset($artifacts_done[$artifact_id])) {
                $node = new TreeNode($this->getArtifactInfo($artifact, $artifacts_info));
                $node->setObject($artifact);
                $artifacts_done[$artifact_id] = true;
                $this->buildArtifactsTree($user, $node, $artifact->getHierarchyLinkedArtifacts($user), $artifacts_info, $excluded_artifact_ids, $artifacts_done);
                $root->addChild($node);
            }
        }
    }

    /**
     * Return artifact info from artifact object
     *
     * If there is already an artifact info available in DB result, use this one
     * instead of re-creating it (artifact_info from DB contains extra informations
     * like the "artifact link column value")
     *
     * @param Tracker_Artifact $artifact
     * @param array $artifacts_info
     *
     * @return array
     */
    private function getArtifactInfo(Tracker_Artifact $artifact, array $artifacts_info) {
        if (isset($artifacts_info[$artifact->getId()])) {
            return $artifacts_info[$artifact->getId()];
        } else {
            return array(
                'id'                => $artifact->getId(),
                'last_changeset_id' => $artifact->getLastChangeset()->getId(),
                'tracker_id'        => $artifact->getTrackerId(),
            );
        }
    }
    
}
?>
