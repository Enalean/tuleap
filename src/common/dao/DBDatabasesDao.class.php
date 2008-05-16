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
    
    function setDefaultCharsetUTF8($name) {
        $sql = 'ALTER DATABASE '. $name .' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
        return $this->update($sql);
    }
}
?>
