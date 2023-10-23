<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use ParagonIE\EasyDB\EasyStatement;
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

    public function searchTimesIdsForUserInTimePeriodByArtifact($user_id, \DateTimeImmutable $start_date, \DateTimeImmutable $end_date, $limit, $offset)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS GROUP_CONCAT(times.id) AS artifact_times_ids
                FROM plugin_timetracking_times AS times
                    INNER JOIN tracker_artifact AS artifacts
                        ON times.artifact_id = artifacts.id
                    INNER JOIN plugin_timetracking_enabled_trackers AS timetracking_trackers
                        ON timetracking_trackers.tracker_id = artifacts.tracker_id
                    INNER JOIN tracker AS tracker
                        ON tracker.id = timetracking_trackers.tracker_id
                    INNER JOIN `groups` AS projects
                        ON tracker.group_id = projects.group_id
                WHERE user_id = ?
                AND   day BETWEEN ? AND ?
                AND projects.status = "A"
                GROUP BY times.artifact_id, times.id
                ORDER BY times.id
                LIMIT ?, ?
        ';

        return $this->getDB()->run($sql, $user_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $offset, $limit);
    }

    public function getLastTime($user_id, $artifact_id)
    {
        $sql = 'SELECT *
                FROM plugin_timetracking_times
                WHERE user_id= ?
                    AND artifact_id = ?
                ORDER BY id DESC
                LIMIT 1';

        return $this->getDB()->row($sql, $user_id, $artifact_id);
    }

    public function getTotalTimeByTracker(array $tracker_ids, \DateTimeImmutable $start_date, \DateTimeImmutable $end_date, string $display_name_sql, int $limit, int $offset)
    {
        $trackers_list = EasyStatement::open();
        $trackers_list->in('artifact.tracker_id IN(?*)', $tracker_ids);

        $sql = "SELECT tracker.id as tracker_id, times.user_id, $display_name_sql, SUM(times.minutes) as minutes
                FROM plugin_timetracking_times as times
                INNER JOIN tracker_artifact as artifact
                          ON artifact.id = times.artifact_id
                INNER JOIN tracker as tracker
                          ON tracker.id = artifact.tracker_id
                INNER JOIN user as user
                          ON user.user_id = times.user_id
                WHERE $trackers_list
                 AND  times.day BETWEEN ? AND ?
                             GROUP BY tracker.id, times.user_id
                             LIMIT ?, ?";
        return $this->getDB()
            ->safeQuery($sql, array_merge($trackers_list->values(), [$start_date->format('Y-m-d'), $end_date->format('Y-m-d'), $offset, $limit]));
    }
}
