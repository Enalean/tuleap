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

/**
 * @deprecated
 */
class CodendiDataAccess
{
    private static $_instance;

    /**
     * @return LegacyDataAccessInterface
     */
    public static function instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new CompatPDODataAccess(DBFactory::getMainTuleapDBConnection());
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
            $data_access_mysql_instance = new self();
        }
        return $data_access_mysql_instance;
    }

    public static function setInstance(LegacyDataAccessInterface $instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }
}
