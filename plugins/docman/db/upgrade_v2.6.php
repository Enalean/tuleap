<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

// Step 1: fix bug with not deleted items
// First delete all documents with deleted parents.
echo "Delete items which parents are already deleted.\n";
$sql = 'UPDATE plugin_docman_item i, plugin_docman_item p' .
' SET i.delete_date = p.delete_date' .
' WHERE p.item_id = i.parent_id' .
' AND p.delete_date IS NOT NULL' .
' AND i.delete_date IS NULL';
$affectedRows = 0;
$aff = 0;
do {
    $affectedRows += $aff;
    $res = db_query($sql);
} while (($aff = db_affected_rows($res)) > 0);
//echo "% Affected rows: ".$affectedRows."\n";

// Step 2:
// Default value upgrade
// Do it only if there is a default_value column
$sql = 'SHOW COLUMNS FROM plugin_docman_metadata LIKE "default_value"';
$res = db_query($sql);
if (db_numrows($res) > 0) {
    // Delete current values associated to folders
    echo "Clean metadata values already affected to a folder (old bug).\n";
    $sql = 'DELETE FROM plugin_docman_metadata_value' .
        ' WHERE item_id IN (SELECT i.item_id FROM plugin_docman_item i WHERE i.item_type = 1)';
    $res = db_query($sql);
    $affectedRows = db_affected_rows($res);
    //echo "% Affected rows: ".$affectedRows."\n";

    // Then, applies default values

    echo "Applies default value defined in properties settings on folders:\n";
    echo "* 'List of values' properties.\n";
    $sql = 'INSERT INTO plugin_docman_metadata_value(item_id, field_id, valueInt)' .
        ' SELECT i.item_id, md.field_id, CASE WHEN love_md.value_id IS NULL THEN 100 ELSE love_md.value_id END' .
        ' FROM plugin_docman_metadata md' .
        ' JOIN plugin_docman_item i USING (group_id)' .
        ' LEFT JOIN plugin_docman_metadata_love_md love_md' .
        '   ON (love_md.value_id = md.default_value' .
        '   AND love_md.field_id = md.field_id)' .
        ' WHERE i.item_type = 1' .
        ' AND i.delete_date IS NULL' .
        ' AND md.data_type = 5' .
        ' AND md.special != 100';
    $res = db_query($sql);
    $affectedRows = db_affected_rows($res);
    //echo "% Affected rows: ".$affectedRows."\n";

    echo "* 'Date' properties.\n";
    $sql = 'INSERT INTO plugin_docman_metadata_value(item_id, field_id, valueDate)' .
        ' SELECT i.item_id, md.field_id, md.default_value' .
        ' FROM plugin_docman_metadata md' .
        ' JOIN plugin_docman_item i USING (group_id)' .
        ' WHERE i.item_type = 1' .
        ' AND i.delete_date IS NULL' .
        ' AND md.data_type = 4' .
        ' AND md.default_value != ""' .
        ' AND md.special != 100';
    $res = db_query($sql);
    $affectedRows = db_affected_rows($res);
    //echo "% Affected rows: ".$affectedRows."\n";

    echo "* 'String' properties.\n";
    $sql = 'INSERT INTO plugin_docman_metadata_value(item_id, field_id, valueString)' .
        ' SELECT i.item_id, md.field_id, md.default_value' .
        ' FROM plugin_docman_metadata md' .
        ' JOIN plugin_docman_item i USING (group_id)' .
        ' WHERE i.item_type = 1' .
        ' AND i.delete_date IS NULL' .
        ' AND md.data_type = 6' .
        ' AND md.default_value != ""' .
        ' AND md.special != 100';
    $res = db_query($sql);
    $affectedRows = db_affected_rows($res);
    //echo "% Affected rows: ".$affectedRows."\n";

    echo "* 'Text' properties.\n";
    $sql = 'INSERT INTO plugin_docman_metadata_value(item_id, field_id, valueText)' .
        ' SELECT i.item_id, md.field_id, md.default_value' .
        ' FROM plugin_docman_metadata md' .
        ' JOIN plugin_docman_item i USING (group_id)' .
        ' WHERE i.item_type = 1' .
        ' AND i.delete_date IS NULL' .
        ' AND md.data_type = 1' .
        ' AND md.default_value != ""' .
        ' AND md.special != 100';
    $res = db_query($sql);
    //echo "% Affected rows: ".$affectedRows."\n";

    echo "Remove old 'default_value' column in properties table\n";
    $sql = 'ALTER TABLE plugin_docman_metadata DROP COLUMN default_value';
    $res = db_query($sql);
    $affectedRows = db_affected_rows($res);
    //echo "% Affected rows: ".$affectedRows."\n";
}

// Step 3: optim
echo "More optimal indexes\n";
$sql = 'ALTER TABLE plugin_docman_metadata DROP INDEX idx_group_id';
$res = db_query($sql);
$sql = 'ALTER TABLE plugin_docman_metadata ADD INDEX idx_group_id (group_id, use_it)';
$res = db_query($sql);
//echo "% Done\n";

echo "~~ Upgrade completed ~~\n";
