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
        $sql = "SELECT * FROM server";
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
        $name        = isset($server['name'])        ? $server['name']        : '';
        $description = isset($server['description']) ? $server['description'] : '';
        $url         = isset($server['url'])         ? $server['url']         : '';
        $sql = sprintf("INSERT INTO server (name, description, url) VALUES (%s, %s, %s)",
				$this->da->quoteSmart($name),
				$this->da->quoteSmart($description),
				$this->da->quoteSmart($url));
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
    function delete($id) {
        $sql = sprintf("DELETE FROM server WHERE id = %s",
            $this->da->quoteSmart($id));
        return $this->update($sql);
    }
    function modify($server) {
        $id          = isset($server['id'])          ? $server['id']          : 'null';
        $name        = isset($server['name'])        ? $server['name']        : '';
        $description = isset($server['description']) ? $server['description'] : '';
        $url         = isset($server['url'])         ? $server['url']         : '';
        $sql = sprintf("UPDATE server SET name = %s, description = %s, url = %s WHERE id = %s",
				$this->da->quoteSmart($name),
				$this->da->quoteSmart($description),
				$this->da->quoteSmart($url),
                $this->da->quoteSmart($id));
        return $this->update($sql);
    }
}


?>