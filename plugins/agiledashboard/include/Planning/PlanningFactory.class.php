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
    public function create($planning_name, $group_id, $backlog_tracker_ids, $planning_tracker_id) {
        return $this->dao->create($planning_name, $group_id, $backlog_tracker_ids, $planning_tracker_id);
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
        return $this->dao->delete($planning_id);
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
