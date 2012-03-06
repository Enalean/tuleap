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
    /**
     * @var Tracker_Hierarchy
     */
    private $hierarchy;
    
    public function __construct(AgileDashboard_SharedFieldFactory $sharedFieldFactory,
                                AgileDashboard_SearchDao          $dao) {
        $this->sharedFieldFactory = $sharedFieldFactory;
        $this->dao                = $dao;
    }

    public function getMatchingArtifacts(array $trackers, Tracker_Hierarchy $hierarchy, $criteria = null) {
        $this->hierarchy = $hierarchy;
        $searchedSharedFields = $this->sharedFieldFactory->getSharedFields($criteria);
        $trackerIds           = array_map(array($this, 'getTrackerId'), $trackers);
        
        if (count($searchedSharedFields) > 0) { 
            return $this->dao->searchMatchingArtifacts($trackerIds, $searchedSharedFields);
        } elseif (count($trackerIds) > 0) {
            if ($artifacts = $this->dao->searchArtifactsFromTrackers($trackerIds)) {
                
                
                $artifactsById      = array();
                $artifactsByTracker = array();
                foreach ($artifacts as $artifact) {
                    //by id
                    $artifactsById[$artifact['artifact_id']] = $artifact;
                    
                    //by tracker_id
                    $tracker_id = $artifact['tracker_id'];
                    if (isset($artifactsByTracker[$tracker_id])) {
                        $artifactsByTracker[$tracker_id][] = $artifact;
                    } else {
                        $artifactsByTracker[$tracker_id] = array($artifact);
                    }
                }
                $result = array();
                usort($trackerIds, array($this, 'sortByTrackerLevel'));
                foreach ($trackerIds as $tracker_id) {
                    foreach ($artifactsByTracker[$tracker_id] as $artifact) {
                        $this->appendArtifactAndSonsToResult($artifact, $result, $artifactsById);
                    }
                }
                
                return array_values($result);
            }
            
        }
        return array();
    }
    
    private function appendArtifactAndSonsToResult($artifact, &$result, $artifacts) {
        if (!isset($result[$artifact['artifact_id']])) {
            $result[$artifact['artifact_id']] = $artifact;
            $artifactlinks = explode(',', $artifact['artifactlinks']);
            foreach ($artifactlinks as $link_id) {
                if (isset($artifacts[$link_id])) {
                    $this->appendArtifactAndSonsToResult($artifacts[$link_id], $result, $artifacts);
                }
            }
        }
    }
    
    protected function sortByTrackerLevel($tracker1, $tracker2) {
        $level1 = $this->hierarchy->getLevel($tracker1);
        $level2 = $this->hierarchy->getLevel($tracker2);
        
        return strcmp($level1, $level2);
    }
    function sortByTrackerId($artifact1, $artifact2) {
        return strcmp($artifact1['tracker_id'], $artifact2['tracker_id']);
    }
    
    
    private function getTrackerId(Tracker $tracker) {
        return $tracker->getId();
    }
    
    private function getTrackerIdsByLevel(Tracker $tracker, $tracker_ids=array(), $hierarchy) {
        $tracker_id = $tracker->getId(); 
        $tracker_ids[$tracker_id] = $hierarchy->getLevel($tracker_id);
        return $tracker_ids;
    }
}
?>
