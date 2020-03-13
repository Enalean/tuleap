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
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';

$logger = new Log_ConsoleLogger();
$dao    = new DataAccessObject();
$dao->enableExceptionsOnError();
$dao->startTransaction();
try {
    $logger->info('Searching for old crosstracker widgets');
    $sql = "SHOW TABLES LIKE 'plugin_tracker_cross_tracker_report%'";
    $tables = $dao->retrieve($sql);
    if (count($tables) !== 2) {
        $logger->info('Tables for old widgets not found. Nothing to migrate.');
        $dao->rollBack();
        exit(0);
    }

    $sql = "SHOW TABLES LIKE 'plugin_crosstracker_report%'";
    $tables = $dao->retrieve($sql);
    if (count($tables) !== 2) {
        $logger->error('Tables for new widgets not found. Please install the plugin crosstracker.');
        $dao->rollBack();
        exit(1);
    }

    $sql = "SELECT COUNT(*) AS nb FROM plugin_tracker_cross_tracker_report";
    $row = $dao->retrieve($sql)->getRow();
    if (! $row || ! $row['nb']) {
        $logger->info("There isn't any widgets to migrate.");
        $dao->rollBack();
        exit(0);
    }

    $logger->info("Nb of widgets to migrate: " . $row['nb']);
    $sql = "INSERT INTO plugin_crosstracker_report (id)
            SELECT id FROM plugin_tracker_cross_tracker_report";
    $dao->update($sql);

    $sql = "INSERT INTO plugin_crosstracker_report_tracker (report_id, tracker_id)
            SELECT report_id, tracker_id FROM plugin_tracker_cross_tracker_report_tracker";
    $dao->update($sql);

    $dao->commit();
    $logger->info("Migration successful. Enjoy!");
    exit(0);
} catch (Exception $e) {
    $logger->error("An error occurred during the migration of widgets :(");
    $logger->error($e->getMessage());
    $dao->rollBack();
    exit(1);
}
