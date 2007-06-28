<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for PriorityPluginHook 
 */
class PriorityPluginHookDao extends DataAccessObject {
    /**
    * Constructs the PriorityPluginHookDao
    * @param $da instance of the DataAccess class
    */
    function PriorityPluginHookDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM priority_plugin_hook";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PriorityPluginHook by PluginId 
    * @return DataAccessResult
    */
    function & searchByPluginId($pluginId) {
        $sql = sprintf("SELECT hook, priority FROM priority_plugin_hook WHERE plugin_id = %s",
                $this->da->quoteSmart($pluginId));
        return $this->retrieve($sql);
    }

    /**
    * Searches PriorityPluginHook by Hook 
    * @return DataAccessResult
    */
    function & searchByHook($hook) {
        $sql = sprintf("SELECT plugin_id, priority FROM priority_plugin_hook WHERE hook = %s",
                $this->da->quoteSmart($hook));
        return $this->retrieve($sql);
    }

    /**
    * Searches PriorityPluginHook by Priority 
    * @return DataAccessResult
    */
    function & searchByPriority($priority) {
        $sql = sprintf("SELECT plugin_id, hook FROM priority_plugin_hook WHERE priority = %s",
                $this->da->quoteSmart($priority));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table priority_plugin_hook 
    * @return true or id(auto_increment) if there is no error
    */
    function create($plugin_id, $hook, $priority) {
        $sql = sprintf("INSERT INTO priority_plugin_hook (plugin_id, hook, priority) VALUES (%s, %s, %s)",
                $this->da->quoteSmart($plugin_id),
                $this->da->quoteSmart($hook),
                $this->da->quoteSmart($priority));
        $inserted = $this->update($sql);
 
        return $inserted;
    }

    
    function &searchByHook_PluginId($hook, $pluginId) {
        $sql = "SELECT priority FROM priority_plugin_hook WHERE hook = '".$hook."' AND plugin_id = '".$pluginId."'";
        return $this->retrieve($sql);
    }
    
    function setPriorityForHook_PluginId($hook, $pluginId, $priority) {
        $updated = false;
        //We search plugin/hook
        if ($dar =& $this->searchByHook_PluginId($hook, $pluginId)) {
            if ($row = $dar->getRow()) {
                if ($row['priority'] == $priority) {
                    //Do nothing, it's the same
                } else {
                    //priority == 0 => erase priority
                    if ($priority == 0) {
                        $updated = $this->update("DELETE FROM priority_plugin_hook WHERE hook = '".$hook."' AND plugin_id = '".$pluginId."'");
                    } else {
                        $updated = $this->update("UPDATE priority_plugin_hook SET priority = '".$priority."' WHERE hook = '".$hook."' AND plugin_id = '".$pluginId."'");
                    }
                }
            } else {
                if ($priority != 0) {
                    $updated = $this->create($pluginId, $hook, $priority);
                }
            }
        }
        return $updated;
    }
    
    function deleteByPluginId($plugin_id) {
        $sql = sprintf("DELETE FROM priority_plugin_hook WHERE plugin_id = %s",
                $this->da->quoteSmart($plugin_id));
        $updated = $this->update($sql);
        return $updated;
    }
}


?>