<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\History;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function hasAccessForTheDay($day, $repository_id, $user_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $day           = $this->da->escapeInt($day);

        $sql = "SELECT 1
                FROM plugin_git_log_read_daily
                WHERE repository_id = $repository_id
                AND user_id = $user_id
                AND day = $day";
        $dar = $this->retrieve($sql);
        return $dar && $dar->count() > 0;
    }

    public function insertGitReadAccess($day, $repository_id, $user_id, $count)
    {
        $day           = $this->da->escapeInt($day);
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $count         = $this->da->escapeInt($count);

        $sql = "INSERT INTO plugin_git_log_read_daily (repository_id, user_id, day, git_read)
                VALUES ($repository_id, $user_id, $day, $count)";

        return $this->update($sql);
    }

    public function addGitReadAccess($day, $repository_id, $user_id, $count)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $user_id       = $this->da->escapeInt($user_id);
        $day           = $this->da->escapeInt($day);
        $count         = $this->da->escapeInt($count);
        $sql = "UPDATE plugin_git_log_read_daily
                SET git_read = git_read + $count
                WHERE repository_id = $repository_id
                AND user_id = $user_id
                AND day = $day";
        return $this->update($sql);
    }

    public function searchAccessPerDay($day)
    {
        $day = $this->da->escapeInt($day);
        $sql = "SELECT repository_id, user_id
                FROM plugin_git_log_read_daily
                WHERE day = $day";
        return $this->retrieve($sql);
    }

    public function searchStatistics($start_date, $end_date, $project_id = null)
    {
        $start_date     = $this->da->quoteSmart($start_date);
        $end_date       = $this->da->quoteSmart($end_date);
        $project_id     = $this->da->escapeInt($project_id);
        $project_filter = "";

        if (! empty($project_id)) {
            $project_filter = " AND project_id = " . $project_id;
        }

        $sql = "SELECT DATE_FORMAT(day, '%M') AS month,
                  YEAR(day) as year,
                  SUM(git_read) as nb
                FROM plugin_git_log_read_daily JOIN plugin_git USING(repository_id)
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                  $project_filter
                GROUP BY year, month
                ORDER BY year, STR_TO_DATE(month,'%M')";

        return $this->retrieve($sql);
    }
}
