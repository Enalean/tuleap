<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginHudsonJob 
 */
class PluginHudsonJobDao extends DataAccessObject {

    /**
    * Gets all jobs in the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM plugin_hudson_job";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginHudsonJob by Codendi group ID 
    * @return DataAccessResult
    */
    function searchByGroupID($group_id) {
        $sql = sprintf("SELECT *  
                        FROM plugin_hudson_job
                        WHERE group_id = %s",
            $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginHudsonJob by job ID 
    * @return DataAccessResult
    */
    function searchByJobID($job_id) {
        $sql = sprintf("SELECT *  
                        FROM plugin_hudson_job
                        WHERE job_id = %s",
            $this->da->quoteSmart($job_id));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginHudsonJob by job name 
    * @return DataAccessResult
    */
    function searchByJobName($job_name) {
        $sql = sprintf("SELECT *  
                        FROM plugin_hudson_job
                        WHERE name = %s",
            $this->da->quoteSmart($job_name));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginHudsonJob by user ID
    * means "all the jobs of all projects the user is member of" 
    * @return DataAccessResult
    */
    function searchByUserID($user_id) {
        $sql = sprintf("SELECT j.*  
                        FROM plugin_hudson_job j, user u, user_group ug
                        WHERE ug.group_id = j.group_id AND
                              u.user_id = ug.user_id AND 
                              u.user_id = %s",
            $this->da->quoteSmart($user_id));
        return $this->retrieve($sql);
    }
    
    /**
    * create a row in the table plugin_hudson_job 
    * @return true if there is no error
    */
    function createHudsonJob($group_id, $hudson_job_url, $job_name, $use_svn_trigger = false, $use_cvs_trigger = false, $token = null) {
        $sql = sprintf("INSERT INTO plugin_hudson_job (group_id, job_url, name, use_svn_trigger, use_cvs_trigger, token) VALUES (%s, %s, %s, %s, %s, %s)",
                $this->da->quoteSmart($group_id),
                $this->da->quoteSmart($hudson_job_url),
                $this->da->quoteSmart($job_name),
                ($use_svn_trigger?1:0),
                ($use_cvs_trigger?1:0),
                (($token !== null)?$this->da->quoteSmart($token):$this->da->quoteSmart(''))
                );
        $ok = $this->update($sql);
		return $ok;
    }
    
    function updateHudsonJob($job_id, $hudson_job_url, $job_name, $use_svn_trigger = false, $use_cvs_trigger = false, $token = null) {
        $sql = sprintf("UPDATE plugin_hudson_job SET job_url = %s, name = %s, use_svn_trigger = %s, use_cvs_trigger = %s, token = %s WHERE job_id = %s",
           		$this->da->quoteSmart($hudson_job_url),
           		$this->da->quoteSmart($job_name),
           		($use_svn_trigger?1:0),
                ($use_cvs_trigger?1:0),
                (($token !== null)?$this->da->quoteSmart($token):$this->da->quoteSmart('')),
                $this->da->quoteSmart($job_id));
        $updated = $this->update($sql);
        return $updated;
    }

    function deleteHudsonJob($job_id) {
        $sql = sprintf("DELETE FROM plugin_hudson_job WHERE job_id = %s",
                $this->da->quoteSmart($job_id));
        $updated = $this->update($sql);
        return $updated;
    }
    
    function deleteHudsonJobsByGroupID($group_id) {
        $sql = sprintf("DELETE FROM plugin_hudson_job WHERE group_id = %s",
                $this->da->quoteSmart($group_id));
        $updated = $this->update($sql);
        return $updated;
    }

    /**
    * Get jobs number
    *
    * @param Integer $groupId Id of the project
    *
    * @return DataAccessResult
    */
    function countJobs($groupId = null) {
        $condition = '';
        if ($groupId) {
            $condition = "AND group_id = ".$this->da->escapeInt($groupId);
        }
        $sql = "SELECT COUNT(*) AS count
                FROM plugin_hudson_job
                JOIN groups USING (group_id)
                WHERE status = 'A'
                  ".$condition;
        return $this->retrieve($sql);
    }

}

?>