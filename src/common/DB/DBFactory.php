<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

final class DBFactory
{
    /**
     * @var array<string, DBConnection>
     */
    private static $connections = [];

    private function __construct()
    {
    }

    public static function getMainTuleapDBConnection(): DBConnection
    {
        return self::getDBConnection(\ForgeConfig::get('sys_dbname'));
    }

    public static function getDBConnection(string $database_name): DBConnection
    {
        if (! isset(self::$connections[$database_name])) {
            self::$connections[$database_name] = new DBConnection(new DBCreator($database_name));
        }

        return self::$connections[$database_name];
    }
}
