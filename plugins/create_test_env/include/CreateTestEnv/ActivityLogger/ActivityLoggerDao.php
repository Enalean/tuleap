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
 *
 */

namespace Tuleap\CreateTestEnv\ActivityLogger;

use Tuleap\DB\DataAccessObject;

class ActivityLoggerDao extends DataAccessObject
{
    public function insert($user_id, $project_id, $service, $action)
    {
        $sql = 'INSERT INTO plugin_create_test_env_activity(user_id, project_id, service, action, time)
                VALUES (?, ?, ?, ?, ?)';
        $this->getDB()->run($sql, $user_id, $project_id, $service, $action, $_SERVER['REQUEST_TIME']);
    }

    public function fetchActivityBetweenDates($from, $to)
    {
        $sql = 'SELECT user_id, user_name, email, service, action, FROM_UNIXTIME(time)
                FROM plugin_create_test_env_activity
                  INNER JOIN user USING (user_id)
                WHERE time BETWEEN ? AND ?';
        return $this->getDB()->run($sql, $from, $to);
    }

    public function purgeOldData($timestamp)
    {
        $sql = 'DELETE FROM plugin_create_test_env_activity WHERE time <= ?';
        return $this->getDB()->run($sql, $timestamp);
    }

    public function getLastWeekActiveUsers()
    {
        return $this->getDB()->run(<<<EOT
            SELECT DISTINCT u.user_id, u.realname, u.user_name, u.email
            FROM plugin_create_test_env_activity a
                INNER JOIN user u ON (u.user_id = a.user_id)
            WHERE a.time >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
            ORDER by a.time ASC
            EOT
        );
    }

    public function getConnexionCount(int $user_id): int
    {
        return (int) $this->getDB()->cell('select count(*) as nb from plugin_create_test_env_activity where user_id = ? and action IN ("Connexion", "Login")', $user_id);
    }

    public function getActionsCount(int $user_id): int
    {
        return (int) $this->getDB()->cell('select count(*) as nb from plugin_create_test_env_activity where user_id = ? group by action', $user_id);
    }

    public function getUsersMinMaxDates(int $user_id): array
    {
        return $this->getDB()->row(
            <<<EOT
            SELECT min(time) as min_time, max(time) as max_time
            FROM plugin_create_test_env_activity
            WHERE user_id = ?
            EOT,
            $user_id
        );
    }

    public function getMinMaxTimeFromLogs(): array
    {
        return $this->getDB()->row(
            <<<EOT
            SELECT min(time) as min_time, max(time) as max_time
            FROM plugin_create_test_env_activity
            EOT
        );
    }

    public function getActionCountBetweenDates(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->getDB()->cell('select count(*) from plugin_create_test_env_activity WHERE time >= ? AND time < ?', $start->getTimestamp(), $end->getTimestamp());
    }

    public function getActionCountPerUsersBetweenDates(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        return $this->getDB()->run('select user_id, count(*) nb FROM plugin_create_test_env_activity WHERE time >= ? AND time < ? group by user_id', $start->getTimestamp(), $end->getTimestamp());
    }
}
