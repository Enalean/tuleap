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
    
    /**
     * @var Tracker_FormElementFactory
     */
    private $formElementFactory;
    
    public function __construct(AgileDashboard_SharedFieldFactory $sharedFieldFactory,
                                AgileDashboard_SearchDao          $dao,
                                Tracker_FormElementFactory        $formElementFactory) {
        
        $this->sharedFieldFactory = $sharedFieldFactory;
        $this->dao                = $dao;
        $this->formElementFactory = $formElementFactory;
    }
    
    public function getMatchingArtifacts(Project $project, $criteria=null) {
        $sharedFields = $this->sharedFieldFactory->getSharedFields($criteria);
        if (count($sharedFields) > 0) { 
            return $this->dao->searchMatchingArtifacts($sharedFields);
        } else {
            $fields     = $this->formElementFactory->getAllProjectSharedFields($project);
            $trackerIds = array_map(array($this, 'getTrackerId'), $fields);
            
            return $this->dao->searchArtifactsFromTrackers($trackerIds);
        }
    }
    
    private function getTrackerId($field) {
        return $field->getTrackerId();
    }
}
?>
