<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('include/DataAccess.class.php');

/**
 *  Data Access Object for Plugin 
 */
class CodexDataAccess extends DataAccess {
    
    function CodexDataAccess() {
      $conn_opt = '';
      if(isset($GLOBALS['sys_enablessl']) && $GLOBALS['sys_enablessl']) {
	$conn_opt = MYSQL_CLIENT_SSL;
      }
      $this->DataAccess($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'], $GLOBALS['sys_dbname'], $conn_opt);
    }
    
    function &instance() {
        static $_codexdataaccess_instance;
        if (!$_codexdataaccess_instance) {
            $_codexdataaccess_instance = new CodexDataAccess();
        }
        return $_codexdataaccess_instance;
    }
}


?>