<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//

require_once('db/DataAccessDbx.class.php');

/**
 *  Data Access Object for Plugin 
 */
class P26CDataAccess extends DataAccessDbx {

    function P26CDataAccess($database, $controler) {
        $module = DBX_OCI8;
        $host = "";
        $db = $controler->getProperty('dimensions_db');
        $password = strtoupper($database)."_REPT";
        $user = strtoupper($database)."_REPT";
	$this->DataAccessDbx($module, $host, $db , $user, $password);
    }
    
    function &instance($database, $controler) {
        static $_svndataaccess_instance;
        if (!$_svndataaccess_instance[$database]) {
            $_svndataaccess_instance[$database] = new P26CDataAccess($database, $controler);
        }
        return $_svndataaccess_instance[$database];
    }
    

}


?>
