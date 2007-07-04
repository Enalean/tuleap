<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
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
        static $_cvsntdataaccess_instance;
        if (!$_cvsntdataaccess_instance[$database]) {
            $_cvsntdataaccess_instance[$database] = new P26CDataAccess($database, $controler);
        }
        return $_cvsntdataaccess_instance[$database];
    }
    

}


?>
