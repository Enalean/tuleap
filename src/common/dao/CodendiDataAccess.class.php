<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2005. Xerox Codendi Team.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\DB\Compat\Legacy2018\CompatPDODataAccess;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\DB\DBFactory;

class CodendiDataAccess extends DataAccess {
    
    public function __construct() {
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

      parent::__construct($credentials, $conn_opt);
    }
    
    protected static $_instance;

    /**
     * @return LegacyDataAccessInterface
     */
    public static function instance()
    {
        if (self::$_instance === null) {
            if (ForgeConfig::get('enable_experimental_compat_pdo_mode')) {
                self::$_instance = new CompatPDODataAccess(DBFactory::getMainTuleapDB());
            } else {
                self::$_instance = self::getDataAccessUsingOriginalMySQLDriverInstance();
            }
        }
        return self::$_instance;
    }

    /**
     * @deprecated
     */
    public static function getDataAccessUsingOriginalMySQLDriverInstance()
    {
        static $data_access_mysql_instance = null;
        if ($data_access_mysql_instance === null) {
            $data_access_mysql_instance = new CodendiDataAccess();
        }
        return $data_access_mysql_instance;
    }

    /**
     * @param LegacyDataAccessInterface $instance
     */
    public static function setInstance(LegacyDataAccessInterface $instance) {
        self::$_instance = $instance;
    }

    public static function clearInstance() {
        self::$_instance = null;
    }
}
