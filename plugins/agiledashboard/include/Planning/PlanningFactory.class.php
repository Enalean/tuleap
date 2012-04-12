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

require_once dirname(__FILE__) .'/../../../tracker/include/Tracker/TrackerFactory.class.php';
require_once('PlanningDao.class.php');
require_once('Planning.class.php');

class PlanningFactory {
    
    /**
     * @var PlanningDao
     */
    private $dao;
    
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    
    public function __construct(PlanningDao $dao, TrackerFactory $tracker_factory) {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }
    
    /**
     * Duplicate plannings for some previously duplicated trackers.
     * 
     * @param int    $group_id         The id of the project where plannings should be created.
     * @param array  $tracker_mapping  An array mapping source tracker ids to destination tracker ids.
     */
    public function duplicatePlannings($group_id, $tracker_mapping) {
        $planning_rows = $this->dao->searchByPlanningTrackerIds(array_keys($tracker_mapping));
        
        foreach($planning_rows as $row) {
            $backlog_tracker_ids = $this->extractCopiedBacklogTrackerIds($row['backlog_tracker_ids'], $tracker_mapping);
            $planning_tracker_id = $tracker_mapping[$row['planning_tracker_id']];
            
            $this->dao->createPlanning($row['name'],
                                       $group_id,
                                       $backlog_tracker_ids,
                                       $planning_tracker_id);
        }
    }
    
    /**
     * @param string $source_backlog_tracker_ids Comma-separated list of the source backlog tracker ids.
     * @param array  $tracker_mapping            Mapping of source => copied tracker ids.
     * 
     * @return array
     */
    private function extractCopiedBacklogTrackerIds($source_backlog_tracker_ids, array $tracker_mapping) {
        $source_backlog_tracker_ids = explode(',', $source_backlog_tracker_ids);
        $backlog_tracker_mapping    = $this->filterByKeys($tracker_mapping, $source_backlog_tracker_ids);
        $copied_backlog_tracker_ids = array_values($backlog_tracker_mapping);
        
        return $copied_backlog_tracker_ids;
    }
    
    /**
     * $tracker_mapping = array(1 => 4,
     *                          2 => 5,
     *                          3 => 6);
     * 
     * $factory->filterByKeys($tracker_mapping, array(1, 3))
     * 
     * => array(1 => 4,
     *          3 => 6)
     * 
     * @param array $array The array to filter.
     * @param array $keys  The keys used for filtering.
     * 
     * @return array
     */
    private function filterByKeys(array $array, array $keys) {
        return array_intersect_key($array, array_flip($keys));
    }
    
    /**
     * Get a list of planning defined in a group_id
     * 
     * @param int $group_id
     *
     * @return array of Planning
     */
    public function getPlannings($group_id) {
        $plannings = array();
        foreach ($this->dao->searchPlannings($group_id) as $row) {
            $plannings[] = new Planning($row['id'], $row['name'], $row['group_id']);
        }
        return $plannings;
    }
    
    /**
     * Get a planning
     * 
     * @param int $group_id
     *
     * @return Planning
     */
    public function getPlanning($planning_id) {
        $planning =  $this->dao->searchById($planning_id)->getRow();
        if ($planning) {
            $backlog_tracker_ids = $this->getAllBacklogTrackerIds($planning_id);
            return new Planning($planning_id, $planning['name'], $planning['group_id'], $backlog_tracker_ids, $planning['planning_tracker_id']);
        }
        return null;
    }
    
    /**
     * Build a new planning in a project
     * 
     * @param int $group_id
     *
     * @return Planning
     */
    public function buildNewPlanning($group_id) {
        return new Planning(null, null, $group_id);
    }
    
    /**
     * Get a list of tracker ids defined as backlog for a planning
     * 
     * @param int $planning_id
     *
     * @return array of tracker id
     */
    public function getAllBacklogTrackerIds($planning_id) {
        $backlog_tracker_ids = array();
        foreach ($this->dao->searchBacklogTrackersById($planning_id) as $row) {
            $backlog_tracker_ids[] = $row['tracker_id'];
        }
        return $backlog_tracker_ids;
    }
    
    /**
     * Create a new planning
     * 
     * @param $planning_name the planning name
     * @param int $group_id
     * @param array $backlog_tracker_ids the list of tracker ids defined as backlog
     * @param int $planning_tracker_id
     *
     * @return array of Planning
     */
    public function createPlanning($planning_name, $group_id, $backlog_tracker_ids, $planning_tracker_id) {
        return $this->dao->createPlanning($planning_name, $group_id, $backlog_tracker_ids, $planning_tracker_id);
    }
    
    public function updatePlanning($planning_id, $planning_name, $backlog_tracker_ids, $planning_tracker_id) {
        return $this->dao->updatePlanning($planning_id, $planning_name, $backlog_tracker_ids, $planning_tracker_id);
    }
    
    /**
     * Delete planning
     * 
     * @param $planning_id the id of the planning
     *
     * @return bool
     */
    public function deletePlanning($planning_id) {
        return $this->dao->deletePlanning($planning_id);
    }
    
    /**
     * @param int $group_id the project id the trackers to retrieve belong to
     * 
     * @return Array of Tracker
     */
    public function getAvailableTrackers($group_id) {
        return array_values($this->tracker_factory->getTrackersByGroupId($group_id));
    }
    
    /**
     * @return TrackerFactory
     */
    public function getTrackerFactory() {
        return $this->tracker_factory;
    }
}

?>
