<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{

    public function startTransaction()
    {
        $this->getDB()->beginTransaction();
    }

    public function commit()
    {
        $this->getDB()->commit();
    }

    public function addGitReadAccess($day, $repository_id, $user_id, $count)
    {
        $sql = 'INSERT INTO plugin_git_log_read_daily (repository_id, user_id, day, git_read)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE git_read = git_read + ?';

        $this->getDB()->run($sql, $repository_id, $user_id, $day, $count, $count);
    }

    public function searchStatistics($start_date, $end_date, $project_id = null)
    {
        $project_filter = EasyStatement::open();

        if (! empty($project_id)) {
            $project_filter->andWith('AND project_id = ?', $project_id);
        }

        $sql = "SELECT DATE_FORMAT(day, '%M') AS month,
                  YEAR(day) as year,
                  SUM(git_read) as nb
                FROM plugin_git_log_read_daily JOIN plugin_git USING(repository_id)
                WHERE day BETWEEN DATE_FORMAT(?, '%Y%m%d') AND DATE_FORMAT(?, '%Y%m%d')
                  $project_filter
                GROUP BY year, month
                ORDER BY year, STR_TO_DATE(month,'%M')";

        $params = [$start_date, $end_date];
        $params = array_merge($params, $project_filter->values());

        return $this->getDB()->safeQuery($sql, $params);
    }
}
