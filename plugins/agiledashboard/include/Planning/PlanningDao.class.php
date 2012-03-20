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

require_once('common/dao/include/DataAccessObject.class.php');

class PlanningDao extends DataAccessObject {
    
    function create($planning_name, $group_id, $planning_backlog_ids, $planning_release_id){
        $planning_name       = $this->da->quoteSmart($planning_name);
        $group_id            = $this->da->escapeInt($group_id);
        $planning_release_id = $this->da->escapeInt($planning_release_id);
        
        $sql = "INSERT INTO plugin_agiledashboard_planning
                    (name, group_id, release_tracker_id)
                    VALUES ($planning_name, $group_id, $planning_release_id)";
        
        $last_id = $this->updateAndGetLastId($sql);
        
        foreach ($planning_backlog_ids as $planning_backlog_id) {            
            $planning_backlog_id = $this->da->escapeInt($planning_backlog_id);
            $sql = "INSERT INTO plugin_agiledashboard_planning_backlog_tracker
                    (planning_id, tracker_id)
                    VALUES ($last_id, $planning_backlog_id)";
            $this->update($sql);
        }
    }
    
    function searchPlannings($group_id){
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT * 
                FROM plugin_agiledashboard_planning
                WHERE group_id = $group_id";
        return $this->retrieve($sql);
    }
}
?>