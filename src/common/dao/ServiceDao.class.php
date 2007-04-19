<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Service 
 */
class ServiceDao extends DataAccessObject {
    /**
    * Constructs the ServiceDao
    * @param $da instance of the DataAccess class
    */
    function ServiceDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Searches Service by Server Id 
    * @return DataAccessResult
    */
    function & searchByServerId($server_id) {
        $sql = sprintf("SELECT * FROM service WHERE server_id = %s ORDER BY group_id, rank",
                $this->da->quoteSmart($server_id));
        return $this->retrieve($sql);
    }
}


?>