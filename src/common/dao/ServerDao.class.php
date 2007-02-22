<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Server 
 */
class ServerDao extends DataAccessObject {
    /**
    * Constructs the ServerDao
    * @param $da instance of the DataAccess class
    */
    function ServerDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    function &searchAll() {
        $sql = "SELECT * FROM server ORDER BY id";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Server 
    * @return DataAccessResult
    */
    function & searchById($id) {
        $sql = sprintf("SELECT * FROM server WHERE id = %s",
				$this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table Server 
    * @return true if there is no error
    */
    function create($server) {
        $id          = isset($server['id'])          ? $server['id']          : '';
        $name        = isset($server['name'])        ? $server['name']        : '';
        $description = isset($server['description']) ? $server['description'] : '';
        $http        = isset($server['http'])        ? $server['http']        : '';
        $https       = isset($server['https'])       ? $server['https']       : '';
        $sql = sprintf("INSERT INTO server (id, name, description, http, https) VALUES (%s, %s, %s, %s, %s)",
				$this->da->quoteSmart($id),
				$this->da->quoteSmart($name),
				$this->da->quoteSmart($description),
				$this->da->quoteSmart($http),
				$this->da->quoteSmart($https));
        return $this->update($sql);
    }
    function delete($id) {
        $sql = sprintf("DELETE FROM server WHERE id = %s",
            $this->da->quoteSmart($id));
        return $this->update($sql);
    }
    function modify($server_id, $server) {
        $id          = isset($server['id'])          ? $server['id']          : $server_id;
        $name        = isset($server['name'])        ? $server['name']        : '';
        $description = isset($server['description']) ? $server['description'] : '';
        $http        = isset($server['http'])        ? $server['http']        : '';
        $https       = isset($server['https'])       ? $server['https']       : '';
        $sql = sprintf("UPDATE server SET id = %s, name = %s, description = %s, http = %s, https = %s WHERE id = %s",
                $this->da->quoteSmart($id),
				$this->da->quoteSmart($name),
				$this->da->quoteSmart($description),
				$this->da->quoteSmart($http),
				$this->da->quoteSmart($https),
                $this->da->quoteSmart($server_id));
        return $this->update($sql);
    }
}


?>