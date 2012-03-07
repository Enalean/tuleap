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
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/FormElement/Tracker_FormElementFactory.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Hierarchy/Hierarchy.class.php';

class AgileDashboard_Search {
    
    /**
     * @var AgileDashboard_SharedFieldFactory
     */
    private $sharedFieldFactory;
    
    /**
     * @var AgileDashboard_SearchDao
     
     */
    private $dao;
    
    public function __construct(AgileDashboard_SharedFieldFactory $sharedFieldFactory,
                                AgileDashboard_SearchDao          $dao) {
        $this->sharedFieldFactory = $sharedFieldFactory;
        $this->dao                = $dao;
    }

    public function getMatchingArtifacts(array $trackers, Tracker_Hierarchy $hierarchy, $criteria = null) {
        $searchedSharedFields = $this->sharedFieldFactory->getSharedFields($criteria);
        $trackerIds           = array_map(array($this, 'getTrackerId'), $trackers);
        $artifacts            = array();
        
        if (count($searchedSharedFields) > 0) { 
            $artifacts = $this->dao->searchMatchingArtifacts($trackerIds, $searchedSharedFields);
        } elseif (count($trackerIds) > 0) {
            $artifacts = $this->dao->searchArtifactsFromTrackers($trackerIds);
        }
        return $this->sortResults($artifacts, $trackerIds, $hierarchy);
    }
    
    private function sortResults($artifacts, $trackerIds, $hierarchy) {
        $root = new TreeNode();
        $root->setId(0);
        if ($artifacts) {
            list($artifactsById, $artifactsByTracker) = $this->indexArtifactsByIdAndTracker($artifacts);
            $artifactsInTree = array();
            $trackerIds = $this->sortTrackerIdsAccordinglyToHierarchy($trackerIds, $hierarchy);
            foreach ($trackerIds as $tracker_id) {
                foreach ($artifactsByTracker[$tracker_id] as $artifact) {
                    $this->appendArtifactAndSonsToParent($artifact, $artifactsInTree, $root, $artifactsById);
                }
            }
        }
        return $root;
    }
    
    private function appendArtifactAndSonsToParent($artifact, &$artifactsInTree, $parent, $artifacts) {
        $id = $artifact['id'];
        if (!isset($artifactsInTree[$id])) {
            $node = new TreeNode();
            $node->setId($id);
            $node->setData($artifact);
            $parent->addChild($node);
            $artifactsInTree[$id] = true;
            $artifactlinks = explode(',', $artifact['artifactlinks']);
            foreach ($artifactlinks as $link_id) {
                if (isset($artifacts[$link_id])) {
                    $this->appendArtifactAndSonsToParent($artifacts[$link_id], $artifactsInTree, $node, $artifacts);
                }
            }
        }
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
    
    private function sortTrackerIdsAccordinglyToHierarchy($trackerIds, $hierarchy) {
        $this->hierarchyTmp = $hierarchy;
        usort($trackerIds, array($this, 'sortByTrackerLevel'));
        return $trackerIds;
    }
    
    protected function sortByTrackerLevel($tracker1, $tracker2) {
        try {
            $level1 = $this->hierarchyTmp->getLevel($tracker1);
        } catch (Exception $e) {
            return 1;
        }
        try {
            $level2 = $this->hierarchyTmp->getLevel($tracker2);
        } catch (Exception $e) {
            return -1;
        }
        return strcmp($level1, $level2);
    }
    
    private function getTrackerId(Tracker $tracker) {
        return $tracker->getId();
    }
}
?>
