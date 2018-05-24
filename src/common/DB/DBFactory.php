<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\DB;

use ParagonIE\EasyDB\Factory;

class DBFactory
{
    private static $connections = [];

    /**
     * @return \ParagonIE\EasyDB\EasyDB
     */
    public static function getMainTuleapDB()
    {
        return self::getDB(\ForgeConfig::get('sys_dbname'));
    }

    /**
     * @return \ParagonIE\EasyDB\EasyDB
     */
    public static function getDB($database_name)
    {
        if (! isset(self::$connections[$database_name])) {
            self::$connections[$database_name] = Factory::create(
                self::getDSN($database_name),
                \ForgeConfig::get('sys_dbuser'),
                \ForgeConfig::get('sys_dbpasswd')
            );
        }

        return self::$connections[$database_name];
    }

    private static function getDSN($database_name)
    {
        return 'mysql:host=' . \ForgeConfig::get('sys_dbhost') . ';dbname=' . $database_name;
    }
}
