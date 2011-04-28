<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Salome database 
 */
class SalomeDao extends DataAccessObject {
    /**
    * Constructs the SalomeDao
    * @param $da instance of the DataAccess class
    */
    function SalomeDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAllConfig() {
        $sql = "SELECT * FROM CONFIG";
        return $this->retrieve($sql);
    }
    

}


?>