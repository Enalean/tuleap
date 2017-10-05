#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

require_once 'pre.php';

if (isset($argv[1]) && in_array($argv[1], array('help', '-h', '--help'))) {
    echo <<<EOT
USAGE: clean-unused-db.php [go] [id] [id]

Purge mediawiki databases and directory for an healthier system.

By default the tool is in dry-run mode and displays what will be purged

   go      Run the actual purge
   [id]    Id of a project to force

Examples:

    $> clean-unused-db.php

        Display all the tables, databases and directories that can be purged
        Display with a Warning the project that could be purged but that are not

    $> clean-unused-db.php go

        Purge databases, tables and directories.
        Projects that were in warnings are ignored

    $> clean-unused-db.php 273

        Dry run but consider that project 273 (that was warning) will be forced

    $> clean-unused-db.php go 273 352 412

        Purge databases & co and force for projects 273 352 and 412

EOT;
    exit(1);
}

$plugin  = PluginManager::instance()->getPluginByName('mediawiki');
$logger  = new Log_ConsoleLogger();
$cleaner = $plugin->getCleanUnused($logger);

$dry_run = true;
if (isset($argv[1]) && $argv[1] === 'go') {
    $dry_run = false;
}

$force = array();
foreach ($argv as $arg) {
    if (is_numeric($arg)) {
        $force[] = (int) $arg;
    }
}

$cleaner->purge($dry_run, $force);
