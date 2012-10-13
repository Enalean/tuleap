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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/dao/include/DataAccessObject.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/dao/TrackerDao.class.php';

class PlanningDao extends DataAccessObject {
    
    private function getTrackerDao() {
        return new TrackerDao();
    }
    
    function createPlanning($group_id, PlanningParameters $planning_parameters) {
        $planning_name       = $this->da->quoteSmart($planning_parameters->name);
        $backlog_title       = $this->da->quoteSmart($planning_parameters->backlog_title);
        $plan_title          = $this->da->quoteSmart($planning_parameters->plan_title);
        $group_id            = $this->da->escapeInt($group_id);
        $planning_tracker_id = $this->da->escapeInt($planning_parameters->planning_tracker_id);
        
        $sql = "INSERT INTO plugin_agiledashboard_planning
                    (name, group_id, planning_tracker_id, backlog_title, plan_title)
                    VALUES ($planning_name, $group_id, $planning_tracker_id, $backlog_title, $plan_title)";
        
        $last_id = $this->updateAndGetLastId($sql);
        
        $this->createBacklogTracker($last_id, $planning_parameters->backlog_tracker_id);
    }
    
    function createBacklogTracker($planning_id, $backlog_tracker_id) {
        $planning_id = $this->da->escapeInt($planning_id);
        $backlog_tracker_id = $this->da->escapeInt($backlog_tracker_id);
        
        $sql = "INSERT INTO plugin_agiledashboard_planning_backlog_tracker
                (planning_id, tracker_id)
                VALUES ($planning_id, $backlog_tracker_id)";
        $this->update($sql);
    }
    
    function searchPlannings($group_id){
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT * 
                FROM plugin_agiledashboard_planning
                WHERE group_id = $group_id";
        return $this->retrieve($sql);
    }
    
    function searchById($planning_id){
        $planning_id = $this->da->escapeInt($planning_id);
        $sql = "SELECT * 
                FROM plugin_agiledashboard_planning
                WHERE id = $planning_id";        
        return $this->retrieve($sql);
    }
    
    public function searchByPlanningTrackerId($planning_tracker_id) {
        $planning_tracker_id = $this->da->escapeInt($planning_tracker_id);
        $sql = "SELECT * 
                FROM plugin_agiledashboard_planning
                WHERE planning_tracker_id = $planning_tracker_id";        
        return $this->retrieve($sql);
    }
    
    function searchByPlanningTrackerIds(array $planning_tracker_ids) {
        $planning_tracker_ids = $this->da->escapeIntImplode($planning_tracker_ids);
        
        $sql = "
            SELECT p.*,
                   b.tracker_id AS backlog_tracker_id
            
            FROM      plugin_agiledashboard_planning                 AS p
            LEFT JOIN plugin_agiledashboard_planning_backlog_tracker AS b ON p.id = b.planning_id
            
            WHERE planning_tracker_id IN ($planning_tracker_ids)
            GROUP BY p.id;
        ";
        
        return $this->retrieve($sql);
    }

    public function searchByBacklogTrackerId($backlog_tracker_id) {
        $backlog_tracker_id = $this->da->escapeInt($backlog_tracker_id);
        $sql = "
            SELECT p.*,
                   b.tracker_id AS backlog_tracker_id

            FROM      plugin_agiledashboard_planning                  AS p
            INNER JOIN plugin_agiledashboard_planning_backlog_tracker AS b ON p.id = b.planning_id

            WHERE b.tracker_id = $backlog_tracker_id
            GROUP BY p.id;
        ";
        return $this->retrieve($sql);
    }

    function searchBacklogTrackerById($planning_id){
        $planning_id = $this->da->escapeInt($planning_id);
        // TODO: Merge table 'plugin_agiledashboard_planning_backlog_tracker' into 'plugin_agiledashboard_planning'
        $sql = "SELECT *
                FROM plugin_agiledashboard_planning_backlog_tracker
                WHERE planning_id = $planning_id";
        return $this->retrieveFirstRow($sql);
    }
    
    function searchPlanningTrackerIdsByGroupId($group_id) {
        $group_id = $this->da->escapeInt($group_id);
        
        $sql = "SELECT planning_tracker_id AS id
                FROM plugin_agiledashboard_planning
                WHERE group_id = $group_id";
        
        /* TODO:
         *   return $this->retrieveIds($sql);
         *   (needs trunk merge)
         */
        $ids = array();
        foreach($this->retrieve($sql) as $row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }
    
    public function searchNonPlanningTrackersByGroupId($group_id) {
        $planning_tracker_ids = $this->searchPlanningTrackerIdsByGroupId($group_id);
        $tracker_dao          = $this->getTrackerDao();
        
        return $tracker_dao->searchByGroupIdWithExcludedIds($group_id, $planning_tracker_ids);
    }
    
    function updatePlanning($planning_id, PlanningParameters $planning_parameters) {
        $planning_id         = $this->da->escapeInt($planning_id);
        $planning_name       = $this->da->quoteSmart($planning_parameters->name);
        $backlog_title       = $this->da->quoteSmart($planning_parameters->backlog_title);
        $plan_title          = $this->da->quoteSmart($planning_parameters->plan_title);
        $planning_tracker_id = $this->da->escapeInt($planning_parameters->planning_tracker_id);
        
        $sql = "UPDATE plugin_agiledashboard_planning
                SET name                = $planning_name,
                    planning_tracker_id = $planning_tracker_id, 
                    backlog_title       = $backlog_title, 
                    plan_title          = $plan_title
                WHERE id = $planning_id";
        $this->update($sql);
        
        $this->deletePlanningBacklogTracker($planning_id);
        $this->createBacklogTracker($planning_id, $planning_parameters->backlog_tracker_id);
    }
    
    function deletePlanning($planning_id) {
        $planning_id = $this->da->escapeInt($planning_id);
        $sql = "DELETE FROM plugin_agiledashboard_planning
                WHERE id=$planning_id";
        $this->update($sql);
        
        $this->deletePlanningBacklogTracker($planning_id);
    }
    
    function deletePlanningBacklogTracker($planning_id) {
        $planning_id = $this->da->escapeInt($planning_id);
        $sql = "DELETE FROM plugin_agiledashboard_planning_backlog_tracker
                WHERE planning_id=$planning_id";
        $this->update($sql);
    }
}
?>