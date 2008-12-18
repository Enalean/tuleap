<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonJobJobDao
 */
require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginHudsonJob 
 */
class PluginHudsonJobDao extends DataAccessObject {
    /**
    * Constructs the PluginHudsonJobDao
    * @param $da instance of the DataAccess class
    */
    function PluginHudsonJobDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all jobs in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM plugin_hudson_job";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginHudsonJob by Codendi group ID 
    * @return DataAccessResult
    */
    function & searchByGroupID($group_id) {
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
    function & searchByJobID($job_id) {
        $sql = sprintf("SELECT *  
                        FROM plugin_hudson_job
                        WHERE job_id = %s",
            $this->da->quoteSmart($job_id));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginHudsonJob by user ID
    * means "all the jobs of all projects the user is member of" 
    * @return DataAccessResult
    */
    function & searchByUserID($user_id) {
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
    function createHudsonJob($group_id, $hudson_job_url) {
        $sql = sprintf("INSERT INTO plugin_hudson_job (group_id, job_url) VALUES (%s, %s)",
                $this->da->quoteSmart($group_id),
                $this->da->quoteSmart($hudson_job_url));
        $ok = $this->update($sql);
		return $ok;
    }
    
    function updateHudsonJob($job_id, $hudson_job_url) {
        $sql = sprintf("UPDATE plugin_hudson_job SET job_url = %s WHERE job_id = %s",
           		$this->da->quoteSmart($hudson_job_url),
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
    
}

?>