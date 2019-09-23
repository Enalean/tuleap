<?php
/**
* Copyright Enalean (c) 2015 - 2018. All rights reserved.
*
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/statisticsPlugin.php';

$disk_data_purger = new Statistics_DiskUsagePurger(
    new Statistics_DiskUsageDao(),
    new Log_ConsoleLogger()
);

try {
    $disk_data_purger->purge(strtotime(date('Y-m-d 00:00:00')));

    $configuration_manager = new Statistics_ConfigurationManager(
        new Statistics_ConfigurationDao()
    );
    $configuration_manager->activateDailyPurge();
} catch (Statistics_PHPVersionException $e) {
    echo $e->getMessage() . PHP_EOL;
}
