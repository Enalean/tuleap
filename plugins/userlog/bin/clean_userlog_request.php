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

$unsigned_int_validator = new Valid_UInt();
if ($argc !== 2 || ! $unsigned_int_validator->validate($argv[1])) {
    fwrite(STDERR, "Usage: {$argv[0]} number_of_months_to_keep" . PHP_EOL);
    exit(1);
}

echo 'The cleaning can be quite long, please wait…';

$number_of_months_to_keep = $argv[1];

removePotentiallyUnusedColumns();
purgeUserlogRequestTable($number_of_months_to_keep);


function removePotentiallyUnusedColumns()
{
    if (! doesColumnExist('plugin_userlog_request', 'session_hash')) {
        return;
    }

    $sql = 'ALTER TABLE plugin_userlog_request DROP COLUMN session_hash';

    $data_access = CodendiDataAccess::instance();
    $result      = $data_access->query($sql);

    if ($result->isError()) {
        fwrite(STDERR, 'An error occurred while removing the session_hash field from the plugin_userlog_request table' . PHP_EOL);
        exit(1);
    }
}

/**
 * @return bool
 */
function doesColumnExist($table_name, $column_name)
{
    $data_access = CodendiDataAccess::instance();

    $database_name = $data_access->quoteSmart($data_access->db_name);
    $table_name    = $data_access->quoteSmart($table_name);
    $column_name   = $data_access->quoteSmart($column_name);

    $sql = "SELECT * FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = $database_name AND TABLE_NAME = $table_name AND COLUMN_NAME = $column_name";

    $result = $data_access->query($sql);

    if ($result->isError()) {
        fwrite(STDERR, "An error occurred while checking if the column $column_name exists in the table $table_name" . PHP_EOL);
        exit(1);
    }

    return $result->getRow() !== false;
}

function purgeUserlogRequestTable($number_of_months_to_keep)
{
    $month_interval      = new DateInterval("P${number_of_months_to_keep}M");
    $oldest_date_to_keep = new DateTime();
    $oldest_date_to_keep->sub($month_interval);

    $data_access              = CodendiDataAccess::instance();
    $oldest_timestamp_to_keep = $data_access->escapeInt($oldest_date_to_keep->getTimestamp());

    $sql    = "DELETE FROM plugin_userlog_request WHERE $oldest_timestamp_to_keep > time";
    $result = $data_access->query($sql);

    if ($result->isError()) {
        fwrite(STDERR, 'An error occurred while purging the old data from the plugin_userlog_request table' . PHP_EOL);
        exit(1);
    }
}
