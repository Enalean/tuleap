<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSvntodimensionsLog 
 */
class PluginSvntodimensionsLogDao extends DataAccessObject {
    /**
    * Constructs the PluginSvntodimensionsLogDao
    * @param $da instance of the DataAccess class
    */
    function PluginSvntodimensionsLogDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM plugin_svntodimensions_log";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSvntodimensionsLog by GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT date, tag, design_part, user_id, state, error FROM plugin_svntodimensions_log WHERE group_id = %s ORDER BY date DESC",
				$this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }
    
     /**
    * Searches PluginSvntodimensionsLog by GroupId and Tag
    * @return DataAccessResult
    */
    function & searchByGroupIdTagAndState($groupId, $tag, $state) {
        $sql = sprintf("SELECT date, user_id FROM plugin_svntodimensions_log WHERE group_id = %s AND tag = %s AND state = %s",
				$this->da->quoteSmart($groupId),
				$this->da->quoteSmart($tag),
				$this->da->quoteSmart($state));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginSvntodimensionsLog by Date 
    * @return DataAccessResult
    */
    function & searchByDate($date) {
        $sql = sprintf("SELECT group_id, tag, user_id, state FROM plugin_svntodimensions_log WHERE date = %s",
				$this->da->quoteSmart($date));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginSvntodimensionsLog by Tag 
    * @return DataAccessResult
    */
    function & searchByTag($tag) {
        $sql = sprintf("SELECT group_id, date, user_id, state FROM plugin_svntodimensions_log WHERE tag = %s",
				$this->da->quoteSmart($tag));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginSvntodimensionsLog by state and groupId
    * state = 0(success), 1(in progress), 2(failure)
    * @return DataAccessResult
    */
    function & searchByStateAndGroupId($groupId, $state ) {
        $sql = sprintf("SELECT date, tag, design_part, user_id FROM plugin_svntodimensions_log WHERE group_id = %s AND state = %s",
				$this->da->quoteSmart($groupId),
				$this->da->quoteSmart($state));
        return $this->retrieve($sql);
    }
    
        /**
    * Searches PluginSvntodimensionsLog by UserId 
    * @return DataAccessResult
    */
    function & searchByUserId($userId) {
        $sql = sprintf("SELECT group_id, date, tag, state, error FROM plugin_svntodimensions_log WHERE user_id = %s",
				$this->da->quoteSmart($userId));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table plugin_svntodimensions_log 
    * state : 0=succes, 1=in progress,  4=failed during copy on dimensions error dmcli, 3 = transfer failed other errors
    * @return true or id(auto_increment) if there is no error
    */
    function create($group_id, $date, $tag, $pl, $user_id, $state, $error = null) {
		$sql = sprintf("INSERT INTO plugin_svntodimensions_log (group_id, date, tag, design_part, user_id, state, error) VALUES (%s, %s, %s, %s, %s, %s, %s)",
				$this->da->quoteSmart($group_id),
				$this->da->quoteSmart($date),
				$this->da->quoteSmart($tag),
                                $this->da->quoteSmart($pl),
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($state),
                $this->da->quoteSmart($error));
        $inserted = $this->update($sql);
 
        return $inserted;
    }
    
    /**
    * Update a row in the table plugin_svntodimensions_log
    * @return true or id(auto_increment) if there is no error
    */
    function updateByTagAndState($tag, $state, $error = null){
    	$sql = "UPDATE plugin_svntodimensions_log SET state = '".$state."', error=".$this->da->quoteSmart($error)." 
    			WHERE state = '1' AND tag = '".$tag."'";
    	$inserted = $this->update($sql);
    	return $inserted;
    }
    
    /**
    * Delete a row in the table plugin_svntodimensions_log
    * @return true or id(auto_increment) if there is no error
    */
    function delete($log_id) {
         $sql = sprintf("DELETE FROM plugin_svntodimensions_log WHERE log_id = %s ",
                $this->da->quoteSmart($log_id));
        return $this->update($sql);
    }

}


?>
