<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 *  Data Access Object for Plugin
 */
class PluginDao extends DataAccessObject
{
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM plugin";
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Id
    * @return DataAccessResult
    */
    public function searchById($id)
    {
        $sql = sprintf(
            "SELECT * FROM plugin WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Name
    * @return DataAccessResult
    */
    public function searchByName($name)
    {
        $sql = sprintf(
            "SELECT * FROM plugin WHERE name = %s",
            $this->da->quoteSmart($name)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Available
    * @return DataAccessResult
    */
    public function searchByAvailable($available)
    {
        $sql = sprintf(
            "SELECT * FROM plugin WHERE available = %s ORDER BY id",
            $this->da->quoteSmart($available)
        );
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table plugin
    * @return true or id(auto_increment) if there is no error
    */
    public function create($name, $available)
    {
        $sql = sprintf(
            "INSERT INTO plugin (name, available) VALUES (%s, %s);",
            $this->da->quoteSmart($name),
            $this->da->quoteSmart($available)
        );
        return $this->updateAndGetLastId($sql);
    }

    public function updateAvailableByPluginId($available, $id)
    {
        $sql = sprintf(
            "UPDATE plugin SET available = %s WHERE id = %s",
            $this->da->quoteSmart($available),
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function removeById($id)
    {
        $sql = sprintf(
            "DELETE FROM plugin WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function restrictProjectPluginUse($pluginId, $restrict)
    {
        $_usage = ($restrict === true ? 1 : 0);
        $sql = sprintf(
            'UPDATE plugin' .
                       ' SET prj_restricted = %d' .
                       ' WHERE id = %d',
            $_usage,
            $pluginId
        );
        return $this->update($sql);
    }

    public function searchProjectPluginRestrictionStatus($pluginId)
    {
        $sql = sprintf(
            'SELECT prj_restricted' .
                       ' FROM plugin' .
                       ' WHERE id = %d',
            $pluginId
        );
        return $this->retrieve($sql);
    }

    public function searchAvailableAndPriorities()
    {
        $sql = "SELECT p.*, h.hook AS hook, h.priority AS priority
                FROM priority_plugin_hook h RIGHT JOIN plugin p ON (h.plugin_id = p.id) 
                WHERE p.available = 1";
        return $this->retrieve($sql);
    }

    public function getAvailablePluginsWithoutOrder()
    {
        $sql = 'SELECT * FROM plugin WHERE available = 1';
        return $this->retrieve($sql);
    }
}
