<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginCvstodimensionsModules 
 */
class PluginCvstodimensionsModulesDao extends DataAccessObject {
    /**
    * Constructs the PluginCvstodimensionsModulesDao
    * @param $da instance of the DataAccess class
    */
    function PluginCvstodimensionsModulesDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM plugin_cvstodimensions_modules";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginCvstodimensionsModules by GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT module, design_part FROM plugin_cvstodimensions_modules WHERE group_id = %s",
				$this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginCvstodimensionsModules by GroupId and Module
    * @return DataAccessResult
    */
    function & searchByGroupIdAndModule($groupId, $module) {
        $sql = sprintf("SELECT design_part FROM plugin_cvstodimensions_modules WHERE group_id = %s AND module = %s",
				$this->da->quoteSmart($groupId),
				$this->da->quoteSmart($module));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginCvstodimensionsModules by Module 
    * @return DataAccessResult
    */
    function & searchByModule($module) {
        $sql = sprintf("SELECT group_id, design_part FROM plugin_cvstodimensions_modules WHERE module = %s",
				$this->da->quoteSmart($module));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginCvstodimensionsModules by DesignPart 
    * @return DataAccessResult
    */
    function & searchByDesignPart($designPart) {
        $sql = sprintf("SELECT group_id, module FROM plugin_cvstodimensions_modules WHERE design_part = %s",
				$this->da->quoteSmart($designPart));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table plugin_cvstodimensions_modules 
    * @return true or id(auto_increment) if there is no error
    */
    function create($group_id, $module, $design_part) {
		$sql = sprintf("INSERT INTO plugin_cvstodimensions_modules (group_id, module, design_part) VALUES (%s, %s, %s)",
				$this->da->quoteSmart($group_id),
				$this->da->quoteSmart($module),
				$this->da->quoteSmart($design_part));
        $inserted = $this->update($sql);
 
        return $inserted;
    }
    
    /**
    * delete all rows in the table plugin_cvstodimensions_modules according to the groupId
    * @return true if there is no error
    */
    function deleteByGroupId($group_id) {
        $sql = sprintf("DELETE FROM plugin_cvstodimensions_modules WHERE group_id = %s",
				$this->da->quoteSmart($group_id));
        return $this->update($sql);
    }
    
    /**
    * create a row in the table plugin_cvstodimensions_modules 
    * @return true or id(auto_increment) if there is no error
    */
    function deleteByGroupIdAndModule($group_id, $module) {
        $sql = sprintf("DELETE FROM plugin_cvstodimensions_modules WHERE group_id = %s AND module = %s",
				$this->da->quoteSmart($group_id),
				$this->da->quoteSmart($module));
        return $this->update($sql);
    }

}


?>