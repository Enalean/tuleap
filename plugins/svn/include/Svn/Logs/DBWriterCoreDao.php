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

namespace Tuleap\Svn\Logs;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class DBWriterCoreDao extends DataAccessObject
{

    public function updateAccess($project_id, $user_id, $day, $nb_access)
    {
        $sql = 'UPDATE group_svn_full_history
                  SET svn_access_count = svn_access_count + ?
                WHERE group_id = ?
                  AND user_id = ?
                  AND day = ?';

        return $this->getDB()->run($sql, $nb_access, $project_id, $user_id, $day);
    }

    public function insertAccess($project_id, $user_id, $day, $nb_access)
    {
        $sql = 'INSERT INTO group_svn_full_history (group_id, user_id, day, svn_access_count) VALUES (?, ?, ?, ?)';

        return $this->getDB()->run($sql, $project_id, $user_id, $day, $nb_access);
    }

    public function searchProjects(array $project_names)
    {
        $project_names_in_condition = EasyStatement::open()->in('?*', $project_names);

        $sql = "SELECT group_id as project_id, unix_group_name as project_name
                FROM groups
                WHERE unix_group_name IN ($project_names_in_condition)";

        return $this->getDB()->safeQuery($sql, $project_names_in_condition->values());
    }

    public function searchAccessPerDay($day)
    {
        $sql = 'SELECT group_id as project_id, user_id FROM group_svn_full_history WHERE day = ?';

        return $this->getDB()->run($sql, $day);
    }

    public function updateLastAccessDate($user_id, $timestamp)
    {
        $sql = 'UPDATE user_access
                SET last_access_date = ?
                WHERE user_id = ?
                    AND last_access_date < ?';

        return $this->getDB()->run($sql, $timestamp, $user_id, $timestamp);
    }
}
