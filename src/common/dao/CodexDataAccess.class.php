<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id:CodexDataAccess.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
//

require_once('include/DataAccess.class.php');

/**
 *  Data Access Object for Plugin 
 */
class CodexDataAccess extends DataAccess {
    
    function CodexDataAccess() {
        $this->DataAccess($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'], $GLOBALS['sys_dbname']);
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