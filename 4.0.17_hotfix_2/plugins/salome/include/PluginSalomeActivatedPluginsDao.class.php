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
class PluginSalomeActivatedPluginsDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeActivatedPluginsDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeActivatedPluginsDao($da) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Searches ActivatedPlugins by Codendi GroupId and option name
    * @return DataAccessResult
    */
    function searchByGroupId($group_id) {
        $sql = sprintf("SELECT *  
                        FROM plugin_salome_activatedplugins
                        WHERE group_id = %d",
            $this->da->escapeInt($group_id));
        return $this->retrieve($sql);
    }
    
    /**
     * Add a plugin for the group_id
     * @param int $group_id The project id
     * @param array $plugins_name list of plugins
     * @return boolean
     */
    function storePlugins($group_id, $plugins_name) {
        $sql = sprintf("DELETE FROM plugin_salome_activatedplugins WHERE group_id = %d",
            $this->da->escapeInt($group_id));
        if ($this->update($sql)) {
            if (count($plugins_name)) {
                $sql = "INSERT INTO plugin_salome_activatedplugins(group_id, name) VALUES ";
                $i = 0;
                foreach ($plugins_name as $name) {
                    if ($i++) {
                        $sql .= ',';
                    }
                    $sql .= '('. (int)$group_id .', '. $this->da->quoteSmart($name) .')';
                }
                return $this->update($sql);
            }
            return true;
        }
        return false;
    }
}


?>