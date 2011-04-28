<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSalomeGroup 
 */
class PluginSalomeConfigurationDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeConfigurationDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeConfigurationDao($da) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Searches Configuration by Codendi GroupId and option name
    * @return DataAccessResult
    */
    function searchOption($group_id, $option) {
        $sql = sprintf("SELECT *  
                        FROM plugin_salome_configuration
                        WHERE group_id = %d
                          AND name     = %s",
            $this->da->escapeInt($group_id),
            $this->da->quoteSmart($option));
        return $this->retrieve($sql);
    }
    
    /**
    * Updates an option for a group_id 
    * @return boolean
    */
    function updateOption($group_id, $option, $value) {
        $sql = sprintf("INSERT INTO plugin_salome_configuration(group_id, name, value) VALUES (%d, %s, %d)
                        ON DUPLICATE KEY UPDATE value = %d",
            $this->da->escapeInt($group_id),
            $this->da->quoteSmart($option),
            $this->da->escapeInt($value),
            $this->da->escapeInt($value));
        return $this->update($sql);
    }
}


?>