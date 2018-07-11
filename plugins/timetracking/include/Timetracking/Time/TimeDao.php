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

namespace Tuleap\Timetracking\Time;

use Tuleap\DB\DataAccessObject;

class TimeDao extends DataAccessObject
{
    public function addTime($user_id, $artifact_id, $day, $minutes, $step)
    {
        $sql = 'REPLACE INTO plugin_timetracking_times (user_id, artifact_id, minutes, step, day)
                VALUES (?, ?, ?, ?, ?)';

        $this->getDB()->run($sql, $user_id, $artifact_id, $minutes, $step, $day);
    }

    public function deleteTime($time_id)
    {
        $sql = 'DELETE FROM plugin_timetracking_times
                WHERE id = ?';

        return $this->getDB()->run($sql, $time_id);
    }

    public function getTimesAddedInArtifactByUser($user_id, $artifact_id)
    {
        $sql = 'SELECT *
                FROM plugin_timetracking_times
                WHERE user_id = ?
                  AND artifact_id = ?
                ORDER BY day DESC';

        return $this->getDB()->run($sql, $user_id, $artifact_id);
    }

    public function getAllTimesAddedInArtifact($artifact_id)
    {
        $sql = 'SELECT *
                FROM plugin_timetracking_times
                WHERE artifact_id = ?
                ORDER BY day DESC';

        return $this->getDB()->run($sql, $artifact_id);
    }

    public function getTimeByIdForUser($user_id, $time_id)
    {
        $sql = 'SELECT *
                FROM plugin_timetracking_times
                WHERE user_id = ?
                  AND id = ?
                LIMIT 1';

        return $this->getDB()->row($sql, $user_id, $time_id);
    }

    public function updateTime($time_id, $day, $minutes, $step)
    {
        $sql = 'UPDATE plugin_timetracking_times
                SET day = ?, minutes = ?, step = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $day, $minutes, $step, $time_id);
    }

    public function searchTimesIdsForUserInTimePeriodByArtifact($user_id, $start_date, $end_date, $limit, $offset)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS GROUP_CONCAT(times.id) AS artifact_times_ids
                FROM plugin_timetracking_times AS times
                    INNER JOIN tracker_artifact AS artifacts
                        ON times.artifact_id = artifacts.id
                    INNER JOIN plugin_timetracking_enabled_trackers AS trackers
                        ON trackers.tracker_id = artifacts.tracker_id
                WHERE user_id = ?
                AND   day BETWEEN CAST(? AS DATE)
                            AND   CAST(? AS DATE)
                GROUP BY times.artifact_id
                ORDER BY times.id
                LIMIT ?, ?
        ';

        return $this->getDB()->run($sql, $user_id, $start_date, $end_date, $offset, $limit);
    }
}
