<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

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
