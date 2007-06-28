<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Plugin 
 */
class PluginDao extends DataAccessObject {
    /**
    * Constructs the PluginDao
    * @param $da instance of the DataAccess class
    */
    function PluginDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM plugin";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Plugin by Id 
    * @return DataAccessResult
    */
    function & searchById($id) {
        $sql = sprintf("SELECT name, available FROM plugin WHERE id = %s",
                $this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Name 
    * @return DataAccessResult
    */
    function & searchByName($name) {
        $sql = sprintf("SELECT id, available FROM plugin WHERE name = %s",
                $this->da->quoteSmart($name));
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Available 
    * @return DataAccessResult
    */
    function & searchByAvailable($available) {
        $sql = sprintf("SELECT id, name FROM plugin WHERE available = %s",
                $this->da->quoteSmart($available));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table plugin 
    * @return true or id(auto_increment) if there is no error
    */
    function create($name, $available) {
        $sql = sprintf("INSERT INTO plugin (name, available) VALUES (%s, %s);",
                $this->da->quoteSmart($name),
                $this->da->quoteSmart($available));
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        } 
        return $inserted;
    }
    
    function updateAvailableByPluginId($available, $id) {
        $sql = sprintf("UPDATE plugin SET available = %s WHERE id = %s",
                $this->da->quoteSmart($available),
                $this->da->quoteSmart($id));
        return $this->update($sql);
    }
    
    function removeById($id) {
        $sql = sprintf("DELETE FROM plugin WHERE id = %s",
                $this->da->quoteSmart($id));
        return $this->update($sql);
    }

    function searchProjectsForPlugin($pluginId) {
        $sql = sprintf('SELECT project_id'.
                       ' FROM project_plugin'.
                       ' WHERE plugin_id = %d'.
                       ' ORDER BY project_id ASC',
                       $pluginId);
        return $this->retrieve($sql);
    }

    function bindPluginToProject($pluginId, $projectId) {
        $sql = sprintf('INSERT INTO project_plugin(plugin_id, project_id)'.
                       ' VALUES (%d, %d)',
                       $pluginId, $projectId);
        return $this->update($sql);
    }

    function unbindPluginToProject($pluginId, $projectId) {
        $sql = sprintf('DELETE FROM project_plugin'.
                       ' WHERE plugin_id = %d'.
                       ' AND project_id = %d',
                       $pluginId, $projectId);
        return $this->update($sql);
    }

    function truncateProjectPlugin($pluginId) {
        $sql = sprintf('DELETE FROM project_plugin'.
                       ' WHERE plugin_id = %d',
                       $pluginId);
        return $this->update($sql);
    }

    function restrictProjectPluginUse($pluginId, $restrict) {
        $_usage = ($restrict === true ? 1 : 0);
        $sql = sprintf('UPDATE plugin'.
                       ' SET prj_restricted = %d'.
                       ' WHERE id = %d',
                       $_usage, $pluginId);
        return $this->update($sql);
    }

    function searchProjectPluginRestrictionStatus($pluginId) {
        $sql = sprintf('SELECT prj_restricted'.
                       ' FROM plugin'.
                       ' WHERE id = %d',
                       $pluginId);
        return $this->retrieve($sql);
    }
    
    function isPluginAllowedForProject($pluginId, $projectId) {
        $sql = sprintf('SELECT project_id'.
                       ' FROM project_plugin'.
                       ' WHERE plugin_id = %d'.
                       ' AND project_id = %d',
                       $pluginId, $projectId);
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            if($dar->rowCount() > 0) {
                return true;
            }
        }
        return false;
    }

}

?>
