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

require_once 'SharedFieldFactory.class.php';
require_once 'SearchDao.class.php';
require_once dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php';
require_once dirname(__FILE__).'/../Hierarchy/Hierarchy.class.php';

class Tracker_CrossSearch_Search {
    
    /**
     * @var Tracker_CrossSearch_SharedFieldFactory
     */
    private $sharedFieldFactory;
    
    /**
     * @var Tracker_CrossSearch_SearchDao
     
     */
    private $dao;
    
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    
    public function __construct(Tracker_CrossSearch_SharedFieldFactory $sharedFieldFactory,
                                Tracker_CrossSearch_SearchDao          $dao,
                                Tracker_HierarchyFactory               $hierarchy_factory) {
        $this->hierarchy_factory  = $hierarchy_factory;
        $this->sharedFieldFactory = $sharedFieldFactory;
        $this->dao                = $dao;
    }

    
    public function getHierarchicallySortedArtifacts($tracker_ids, Tracker_CrossSearch_Criteria $criteria, $excludedArtifactIds = array()) {
        $hierarchy = $this->hierarchy_factory->getHierarchy($tracker_ids);
        return $this->getMatchingArtifacts($tracker_ids, $hierarchy, $criteria, $excludedArtifactIds);
    }
    
    /**
     * @deprecated
     */
    public function getMatchingArtifacts(array $trackerIds, Tracker_Hierarchy $hierarchy, Tracker_CrossSearch_Criteria $criteria, $excludedArtifactIds = array()) {
        $searchedSharedFields = $this->sharedFieldFactory->getSharedFields($criteria->getSharedFields());
        $title                = $criteria->getTitle();
        $status               = $criteria->getStatus();
        
        $artifacts = $this->dao->searchMatchingArtifacts($trackerIds, $searchedSharedFields, $title, $status, $excludedArtifactIds);
        return $this->sortResults($artifacts, $trackerIds, $hierarchy);
    }
    
    private function sortResults($artifacts, array $trackerIds, Tracker_Hierarchy $hierarchy) {
        $root = new TreeNode();
        $root->setId(0);
        if ($artifacts) {
            list($artifactsById, $artifactsByTracker) = $this->indexArtifactsByIdAndTracker($artifacts);
            $artifactsInTree = array();
            $trackerIds = $this->sortTrackerIdsAccordinglyToHierarchy($trackerIds, $hierarchy);
            foreach ($trackerIds as $tracker_id) {
                if (isset($artifactsByTracker[$tracker_id])) {
                    foreach ($artifactsByTracker[$tracker_id] as $artifact) {
                        $this->appendArtifactAndSonsToParent($artifact, $artifactsInTree, $root, $artifactsById, $hierarchy);
                    }
                }
            }
        }
        return $root;
    }
    
    private function appendArtifactAndSonsToParent(array $artifact, array &$artifactsInTree, TreeNode $parent, array $artifacts, Tracker_Hierarchy $hierarchy) {
        $id = $artifact['id'];
        if (!isset($artifactsInTree[$id])) {
            $node = new TreeNode();
            $node->setId($id);
            $node->setData($artifact);
            $parent->addChild($node);
            $artifactsInTree[$id] = true;
            $artifactlinks = explode(',', $artifact['artifactlinks']);
            foreach ($artifactlinks as $link_id) {
                if ($this->artifactCanBeAppended($link_id, $artifacts, $artifact, $hierarchy)) {
                    $this->appendArtifactAndSonsToParent($artifacts[$link_id], $artifactsInTree, $node, $artifacts, $hierarchy);
                }
            }
        }
    }
    
    private function artifactCanBeAppended($artifact_id, array $artifacts, array $parent_artifact, Tracker_Hierarchy $hierarchy) {
        return isset($artifacts[$artifact_id]) && $hierarchy->isChild($parent_artifact['tracker_id'], $artifacts[$artifact_id]['tracker_id']);
    }
    
    private function indexArtifactsByIdAndTracker($artifacts) {
        $artifactsById      = array();
        $artifactsByTracker = array();
        foreach ($artifacts as $artifact) {
            //by id
            $artifactsById[$artifact['id']] = $artifact;
            
            //by tracker_id
            $tracker_id = $artifact['tracker_id'];
            if (isset($artifactsByTracker[$tracker_id])) {
                $artifactsByTracker[$tracker_id][] = $artifact;
            } else {
                $artifactsByTracker[$tracker_id] = array($artifact);
            }
        }
        return array($artifactsById, $artifactsByTracker);
    }
    
    private function sortTrackerIdsAccordinglyToHierarchy(array $trackerIds, Tracker_Hierarchy $hierarchy) {
        $this->hierarchyTmp = $hierarchy;
        usort($trackerIds, array($this, 'sortByTrackerLevel'));
        return $trackerIds;
    }
    
    protected function sortByTrackerLevel($tracker1_id, $tracker2_id) {
        try {
            $level1 = $this->hierarchyTmp->getLevel($tracker1_id);
        } catch (Exception $e) {
            return 1;
        }
        try {
            $level2 = $this->hierarchyTmp->getLevel($tracker2_id);
        } catch (Exception $e) {
            return -1;
        }
        return strcmp($level1, $level2);
    }
    
}
?>
