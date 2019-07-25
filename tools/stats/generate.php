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

require_once __DIR__ . '/../../src/www/include/pre.php';

function dumpStats($dao, $sql, ZipArchive $archive, $name)
{
    echo "Generating $name\n";
    $is_header_needed = true;

    $fp = fopen("php://temp", 'r+');
    foreach ($dao->retrieve($sql) as $value) {
        if ($is_header_needed) {
            fputcsv($fp, array_keys($value));
            $is_header_needed = false;
        }
        fputcsv($fp, $value);
    }
    rewind($fp);

    $archive->addFromString($name, stream_get_contents($fp));
    fclose($fp);
}

$plugin_manager = PluginManager::instance();
$dao = new DataAccessObject();

$base_path = ForgeConfig::get('tmp_dir') . '/stats';
if (! is_dir($base_path) && ! mkdir($base_path, 0700, true)) {
    die("ERROR: Unable to create $base_path directory\n");
}
$filename = $base_path . '/usage-stats-' . date('Ymd-His') . '.zip';

$archive  = new ZipArchive();
$status   = $archive->open($filename, ZipArchive::CREATE);
if ($status !== true) {
    die("ERROR: Unable to create $filename archive ($status)\n");
}

$sql = "SELECT access, count(*) AS nb
    FROM groups
    WHERE status = 'A'
    GROUP BY access";
dumpStats($dao, $sql, $archive, "private-public-projects.csv");

$sql = "SELECT desc_required, count(*) AS nb
    FROM group_desc
    GROUP BY desc_required";
dumpStats($dao, $sql, $archive, "mandatory-custom-project-fields.csv");

$sql = "SELECT
         user_group.group_id,
         count(user_group.user_id) AS nb
     FROM user_group
         INNER JOIN groups
             ON groups.group_id = user_group.group_id AND groups.status = 'A'
         INNER JOIN user
            ON user.user_id = user_group.user_id AND user.status IN ('A', 'R')
     GROUP BY group_id";
dumpStats($dao, $sql, $archive, "nb-members-per-projects.csv");

$sql = "SELECT
         user_group.user_id,
         count(user_group.group_id) AS nb
     FROM user_group
         INNER JOIN groups
             ON groups.group_id = user_group.group_id AND groups.status = 'A'
         INNER JOIN user
            ON user.user_id = user_group.user_id AND user.status IN ('A', 'R')
     GROUP BY user_id";
dumpStats($dao, $sql, $archive, "nb-projects-per-users.csv");

$sql = "SELECT
        timezone,
        sum(alive),
        sum(notalive)
    FROM (
        SELECT
                 timezone,
                 if(status IN ('A', 'R'), 1, 0) AS alive,
                 if(status IN ('A', 'R'), 0, 1) AS notalive
             FROM user
         ) AS R
    GROUP BY timezone";
dumpStats($dao, $sql, $archive, "nb-users-by-timezone.csv");

$sql = "SELECT frs_package.group_id, count(*) AS nb
     FROM frs_package
         INNER JOIN groups
             ON groups.group_id = frs_package.group_id AND groups.status = 'A'
     GROUP BY frs_package.group_id";
dumpStats($dao, $sql, $archive, "nb-packages-per-projects.csv");

$sql = "SELECT
         frs_package.package_id,
         count(*) AS nb
     FROM frs_release
         INNER JOIN frs_package
             ON frs_release.package_id = frs_package.package_id
         INNER JOIN groups
             ON groups.group_id = frs_package.group_id AND groups.status = 'A'
     GROUP BY frs_package.package_id";
dumpStats($dao, $sql, $archive, "nb-releases-per-packages.csv");

$sql = "SELECT
         frs_release.release_id,
         count(*) AS nb
     FROM frs_file
         INNER JOIN frs_release
             ON frs_file.release_id = frs_release.release_id
         INNER JOIN frs_package
             ON frs_release.package_id = frs_package.package_id
         INNER JOIN groups
             ON groups.group_id = frs_package.group_id AND groups.status = 'A'
     GROUP BY frs_release.release_id";
dumpStats($dao, $sql, $archive, "nb-files-per-releases.csv");

if ($plugin_manager->getAvailablePluginByName('git')) {
    $sql = "SELECT
            project_id,
            count(*) AS nb
        FROM plugin_git
            INNER JOIN groups
                ON groups.group_id = plugin_git.project_id AND groups.status = 'A'
        GROUP BY project_id";
    dumpStats($dao, $sql, $archive, "nb-git-repositories-per-projects.csv");
}


if ($plugin_manager->getAvailablePluginByName('svn')) {
    $sql = "SELECT
            project_id,
            count(*) AS nb
        FROM plugin_svn_repositories
            INNER JOIN groups
                ON groups.group_id = plugin_svn_repositories.project_id AND groups.status = 'A'
        GROUP BY project_id";
    dumpStats($dao, $sql, $archive, "nb-svn-repositories-per-projects.csv");
}

if ($plugin_manager->getAvailablePluginByName('tracker')) {
    $sql = "SELECT
            tracker.group_id,
            count(*) AS nb
        FROM tracker
            INNER JOIN groups
                ON groups.group_id = tracker.group_id AND groups.status = 'A' AND deletion_date IS NULL
        GROUP BY tracker.group_id";
    dumpStats($dao, $sql, $archive, "nb-trackers-per-projects.csv");

    $sql = "SELECT
            tracker.group_id,
            count(*) AS nb
        FROM tracker
            INNER JOIN groups
                ON groups.group_id = tracker.group_id AND groups.status = 'A' AND deletion_date IS NULL
            INNER JOIN tracker_artifact
                ON tracker.id = tracker_artifact.tracker_id
        GROUP BY tracker.group_id";
    dumpStats($dao, $sql, $archive, "nb-artifacts-per-projects.csv");
}

if (! $archive->close()) {
    die("ERROR: Unable to close $filename archive\n");
}

echo "\n$filename created\n\n";
