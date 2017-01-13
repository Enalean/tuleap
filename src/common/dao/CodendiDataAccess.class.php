<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2005. Xerox Codendi Team.
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

require_once('include/DataAccess.class.php');
require_once('include/DataAccessCredentials.class.php');

class CodendiDataAccess extends DataAccess {
    
    protected function __construct() {
      $conn_opt = 0;
      if(isset($GLOBALS['sys_enablessl']) && $GLOBALS['sys_enablessl']) {
          $conn_opt = MYSQL_CLIENT_SSL;
      }

      $credentials = new DataAccessCredentials(
        $GLOBALS['sys_dbhost'],
        $GLOBALS['sys_dbuser'],
        $GLOBALS['sys_dbpasswd'],
        $GLOBALS['sys_dbname']
      );

      $this->DataAccess($credentials, $conn_opt);
    }
    
    protected static $_instance;
    /** @return CodendiDataAccess */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * @param DataAccess $instance
     */
    public static function setInstance(DataAccess $instance) {
        self::$_instance = $instance;
    }

    public static function clearInstance() {
        self::$_instance = null;
    }
}

?>