<?php

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for DB Databases
 */
class DBDatabasesDao extends DataAccessObject {
    /**
    * Constructs the DBDatabasesDao
    * @param $da instance of the DataAccess class
    */
    function DBDatabasesDao( $da ) {
        DataAccessObject::DataAccessObject($da);
    }

    function searchAll() {
        $sql="SHOW DATABASES";
        return $this->retrieve($sql);
    }
    
}
?>
