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

require_once 'AgileDashboardSearchDao.class.php';
require_once 'common/project/Project.class.php';

class AgileDashboardSearch {
    
    function getMatchingArtifacts(Project $project, $criteria) {
        $fields     = $this->retrieveSharedFields($project);
        $trackerIds = $this->retrieveTrackerIds($fields);
        $fieldIds   = $this->retrieveSharedFieldIds($fields);
        $valueIds   = $this->extractValueIds($criteria);
        
        return $this->getDao()->searchMatchingArtifacts($trackerIds, $fieldIds, $valueIds);
    }
    
    protected function retrieveSharedFieldIds(array $fields) {
        $fieldIds = array();
        foreach ($fields as $field) {
            $fieldIds[] = $field->getId();
        }
        return $fieldIds;
    }
    
    protected function retrieveTrackerIds(array $fields) {
        $trackerIds = array();
        foreach($fields as $field) {
            $trackerIds[] = $field->getTracker()->getId();
        }
        return $trackerIds;
    }
    
    protected function retrieveSharedFields(Project $project) {
        return $this->getFormElementFactory()->getProjectSharedFields($project);
    }
    
    protected function extractValueIds($criteria) {
        $sourceOrTargetValueIds   = array();
        foreach ($criteria as $fieldId => $data) {
            foreach ($data['values'] as $valueId) {
                $sourceOrTargetValueIds[] = (int)$valueId;
            }
        }
        $valueIds = array();
        foreach ($this->getDao()->searchSharedValueIds($sourceOrTargetValueIds) as $row) {
            $valueIds[] = $row['id'];
        }
        return $valueIds;
    }
    
    /**
     * Wrapper for tests
     * @return AgileDashboardSearchDao
     */
    protected function getDao() {
        return new AgileDashboardSearchDao();
    }
    
    /**
     * Wrapper for tests
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }

}
?>
