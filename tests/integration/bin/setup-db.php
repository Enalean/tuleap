#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

require_once __DIR__ . '/../../../src/www/include/pre.php';

use ParagonIE\EasyDB\Factory;
use Tuleap\DB\DBConfig;

$db_root = Factory::fromArray([
    DBConfig::getPDODSN('mysql'),
    'root',
    'welcome0',
]);

// Allow all privileges on DB starting with 'testdb_' so we can create and drop database during the tests
$db_root->run('GRANT ALL PRIVILEGES ON `testdb_%` . * TO "' . \ForgeConfig::get(DBConfig::CONF_DBUSER) . '"@"%"');

$db_tables_dao = new \Tuleap\DAO\DBTablesDao();
$db_tables_dao->updateFromFile(__DIR__ . '/../../../src/db/mysql/trackerv3structure.sql');
$db_tables_dao->updateFromFile(__DIR__ . '/../../../src/db/mysql/trackerv3values.sql');
// Need the raw import (instead of std activate of plugin) because we need to load
// for Tv3->Tv5 migration tests
$db_tables_dao->updateFromFile(__DIR__ . '/../../../plugins/tracker_date_reminder/db/install.sql');
$db_tables_dao->updateFromFile(__DIR__ . '/../../../plugins/tracker_date_reminder/db/examples.sql');
