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
}
