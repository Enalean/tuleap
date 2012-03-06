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

    public function getMatchingArtifacts(array $trackers, $criteria = null) {
        $searchedSharedFields = $this->sharedFieldFactory->getSharedFields($criteria);
        $trackerIds           = array_map(array($this, 'getTrackerId'), $trackers);
        
        if (count($searchedSharedFields) > 0) { 
            return $this->dao->searchMatchingArtifacts($trackerIds, $searchedSharedFields);
        } elseif (count($trackerIds) > 0) {
            return $this->dao->searchArtifactsFromTrackers($trackerIds);
        }
        return array();
    }
    
    private function getTrackerId(Tracker $tracker) {
        return $tracker->getId();
    }
}
?>
