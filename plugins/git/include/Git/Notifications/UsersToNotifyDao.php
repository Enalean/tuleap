<?php
/**
 * Copyright Enalean (c) 2017-2018. All rights reserved.
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

namespace Tuleap\Git\Notifications;

use Tuleap\DB\DataAccessObject;

class UsersToNotifyDao extends DataAccessObject
{
    public function searchUsersByRepositoryId($repository_id)
    {
        $sql = "SELECT user.*
                FROM plugin_git_post_receive_notification_user AS notif
                    INNER JOIN user
                    ON (
                        user.user_id = notif.user_id
                        AND notif.repository_id = ?
                        AND user.status IN ('A', 'R')
                    )";

        return $this->getDB()->run($sql, $repository_id);
    }

    public function delete($repository_id, $user_id)
    {
        $sql = 'DELETE
                FROM plugin_git_post_receive_notification_user
                WHERE repository_id = ?
                  AND user_id = ?';

        try {
            $this->getDB()->run($sql, $repository_id, $user_id);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function deleteByRepositoryId($repository_id)
    {
        $sql = 'DELETE
                FROM plugin_git_post_receive_notification_user
                WHERE repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }

    public function insert($repository_id, $user_id)
    {
        $sql = 'REPLACE INTO plugin_git_post_receive_notification_user(repository_id, user_id)
                VALUES (?, ?)';

        try {
            $this->getDB()->run($sql, $repository_id, $user_id);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }
}
