#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

define('EMPTY_FILE_MD5', 'd41d8cd98f00b204e9800998ecf8427e');

require_once __DIR__ . '/../../src/www/include/pre.php';

$option = isset($argv[1]) ? $argv[1] : '';

switch ($option) {
    case "dump":
        $attachments = get_all_attachment_ids();
        dump_attachments($attachments);
        break;

    case "check":
        $attachments = get_all_attachment_ids();
        check_attachments($attachments);
        break;

    case "purge":
        $attachments = get_all_attachment_ids();
        delete_equal_attachments($attachments);
        break;

    case "all":
        $attachments = get_all_attachment_ids();
        dump_attachments($attachments);
        check_attachments($attachments);
        delete_equal_attachments($attachments);
        break;

    default:
        $toolname = basename($argv[0]);
        echo <<<EOT
This tool will dump artifact_file content onto the file system.

3 options:
- $toolname dump    Extract data from DB on filesystem
- $toolname check   Compare data between DB and filesystem (md5sum)
- $toolname purge   Remove data from DB if corresponding file exist and are equal (same md5 sum)
- $toolname all     Dump, then check, then purge

EOT;

        break;
}

function get_all_attachment_ids()
{
    $attachments = array();

    $file_ids = array();
    $res = db_query('
        SELECT id, artifact_id
        FROM artifact_file
    ');
    while ($row = db_fetch_array($res)) {
        $file_ids[$row['artifact_id']][] = $row['id'];
        $attachments[$row['id']] = false;
    }
    db_free_result($res);

    $sql = '
        SELECT artifact_id, group_artifact_id
        FROM artifact
        WHERE artifact_id IN (' . implode(',', array_keys($file_ids)) . ')
    ';
    $res = db_query($sql);
    while ($row = db_fetch_array($res)) {
        foreach ($file_ids[$row['artifact_id']] as $file_id) {
            $attachments[$file_id] = $row['group_artifact_id'];
        }
    }
    db_free_result($res);

    return $attachments;
}

function dump_attachments(array $attachments)
{
    echo "----- Start Dump -----\n";
    foreach ($attachments as $attachment_id => $artifact_type_id) {
        $parent_path = ArtifactFile::getParentDirectoryForArtifactTypeId($artifact_type_id);
        $attachment_path = $parent_path . DIRECTORY_SEPARATOR . $attachment_id;
        if (! is_file($attachment_path)) {
            if (! is_dir($parent_path)) {
                mkdir($parent_path, 0750, true);
            }
            $res = db_query('SELECT bin_data FROM artifact_file WHERE id = ' . $attachment_id);
            if ($res && !db_error($res)) {
                echo "Create $attachment_path\n";
                file_put_contents($attachment_path, db_result($res, 0, 0));
            }
            db_free_result($res);
        }
    }
    echo "----- Dump completed -----\n";
}

function check_attachments(array $attachments)
{
    echo "----- Start Check -----\n";
    foreach ($attachments as $attachment_id => $artifact_type_id) {
        $parent_path = ArtifactFile::getParentDirectoryForArtifactTypeId($artifact_type_id);
        $attachment_path = $parent_path . DIRECTORY_SEPARATOR . $attachment_id;
        if (! is_file($attachment_path)) {
            echo "$attachment_id doesn't exist on file system\n";
        } else {
            $res = db_query('SELECT filesize, md5(bin_data) as md5 FROM artifact_file WHERE id = ' . $attachment_id);
            if ($res && !db_error($res)) {
                $row = db_fetch_array($res);
                if (md5_file($attachment_path) !== $row['md5']) {
                    if ($row['md5'] == EMPTY_FILE_MD5 && $row['filesize'] > 0) {
                        // we assume that file was directly uploaded on file system
                        // it's already new code
                        continue;
                    } else {
                        error("$attachment_path differs from DB. Please delete the file and run dump again");
                    }
                }
            } else {
                error("DB error with attachment $attachment_id");
            }
            db_free_result($res);
        }
    }
    echo "----- Check completed -----\n";
}

function delete_equal_attachments(array $attachments)
{
    echo "----- Start Purge -----\n";
    foreach ($attachments as $attachment_id => $artifact_type_id) {
        $parent_path = ArtifactFile::getParentDirectoryForArtifactTypeId($artifact_type_id);
        $attachment_path = $parent_path . DIRECTORY_SEPARATOR . $attachment_id;
        if (is_file($attachment_path)) {
            $res = db_query('SELECT filesize, md5(bin_data) as md5 FROM artifact_file WHERE id = ' . $attachment_id);
            if ($res && !db_error($res)) {
                $row = db_fetch_array($res);
                if (md5_file($attachment_path) === $row['md5']) {
                    db_query("UPDATE artifact_file SET bin_data = '' WHERE id = $attachment_id");
                    $db_error = db_error();
                    if ($db_error) {
                        error("An error occurred while cleaning-up $attachment_id: $db_error");
                    }
                }
            }
            db_free_result($res);
        }
    }

    echo "----- Purge completed -----\n";
}

function error($str)
{
    echo "*** ERROR: $str\n";
}

?>
