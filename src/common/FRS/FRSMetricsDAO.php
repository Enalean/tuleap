<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Tuleap\DB\DataAccessObject;

class FRSMetricsDAO extends DataAccessObject
{
    public function executeDailyRun(\DateTimeImmutable $now): void
    {
        // build the agregates by project for all times
        $rows_available_project = $this->getDB()->run("SELECT group_id AS project_id FROM `groups` WHERE status='A'");

        $this->getDB()->run('DROP TABLE IF EXISTS frs_dlstats_grouptotal_agg_tmp');
        $this->getDB()->run("CREATE TABLE frs_dlstats_grouptotal_agg_tmp (
            group_id int(11) DEFAULT '0' NOT NULL,
            downloads int(11) DEFAULT '0' NOT NULL,
            KEY idx_stats_agr_tmp_gid (group_id)
        )");

        $rows = $this->getDB()->run('SELECT frs_package.group_id AS project_id, COUNT(*) AS downloads
            FROM filedownload_log,frs_file,frs_release,frs_package
            WHERE frs_file.file_id=filedownload_log.filerelease_id AND
            frs_file.release_id=frs_release.release_id AND
            frs_release.package_id=frs_package.package_id GROUP BY group_id');

        $downloads = [];
        foreach ($rows as $row) {
            $project_id = $row['project_id'];
            if (isset($downloads[$project_id])) {
                $downloads[$project_id] += $row['downloads'];
            } else {
                $downloads[$project_id] = $row['downloads'];
            }
        }

        foreach ($rows_available_project as $row_available_project) {
            $xfers      = 0;
            $project_id = $row_available_project['project_id'];
            if (isset($downloads[$project_id])) {
                $xfers = $downloads[$project_id];
            }

            $this->getDB()->run('INSERT INTO frs_dlstats_grouptotal_agg_tmp VALUES (?, ?)', $project_id, $xfers);
        }

        // Drop the old agregate table
        $this->getDB()->run('DROP TABLE IF EXISTS frs_dlstats_grouptotal_agg');
        // Relocate the new table to take its place
        $this->getDB()->run('ALTER TABLE frs_dlstats_grouptotal_agg_tmp RENAME AS frs_dlstats_grouptotal_agg');

        // Update the agregates by project for the day before
        $time_begin = $now->modify('- 1 day')->setTime(0, 0, 0)->getTimestamp();
        $time_end   = $now->modify('- 1 day')->setTime(23, 59, 59)->getTimestamp();
        $today      = $now->modify('- 1 day')->format('Ymd');

        // POPULATE THE frs_dlstats_group_agg TABLE.
        // Count all the downloads through the Web Frontend  (group by project)
        // managed thorugh a PHP script and there is special table storing download information
        $rows = $this->getDB()->run(
            'SELECT frs_package.group_id AS project_id, COUNT(*) AS nb
            FROM frs_package,frs_release, frs_file, filedownload_log, `groups`
            WHERE filedownload_log.filerelease_id = frs_file.file_id
            AND (filedownload_log.time > ? AND filedownload_log.time <= ?)
            AND frs_file.release_id = frs_release.release_id
            AND frs_release.package_id = frs_package.package_id
            AND frs_package.group_id = `groups`.group_id
            AND `groups`.type = 1
            GROUP BY frs_package.group_id',
            $time_begin,
            $time_end,
        );

        $this->getDB()->run('DELETE FROM frs_dlstats_group_agg WHERE day=?', $today);

        foreach ($rows as $row) {
            $this->getDB()->run('INSERT INTO frs_dlstats_group_agg VALUES (?, ?,?)', $row['project_id'], $today, $row['nb']);
        }

        // POPULATE THE frs_dlstats_file_agg TABLE
        // Count all the downloads through the Web Frontend  (group by file)
        // monitored on Tuleap and there is special table storing download information
        $rows = $this->getDB()->run(
            'SELECT dl.filerelease_id AS file_id, COUNT(*) AS nb
            FROM filedownload_log as dl,`groups`,frs_release,frs_package,frs_file
            WHERE (time >= ? AND time <= ?) AND `groups`.type = 1
            AND frs_package.group_id=`groups`.group_id
            AND frs_release.package_id=frs_package.package_id
            AND frs_file.release_id=frs_release.release_id
            AND dl.filerelease_id = frs_file.file_id GROUP BY filerelease_id',
            $time_begin,
            $time_end,
        );

        $this->getDB()->run('DELETE FROM frs_dlstats_file_agg WHERE day=?', $today);

        foreach ($rows as $row) {
            $this->getDB()->run('INSERT INTO frs_dlstats_file_agg VALUES (?, ?,?)', $row['file_id'], $today, $row['nb']);
        }
    }
}
