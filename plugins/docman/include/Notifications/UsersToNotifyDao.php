<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use DataAccessObject;
use DataAccessResult;

class UsersToNotifyDao extends DataAccessObject
{
    public function searchDocmanMonitoredItems($project_id, $user_id)
    {
        $sql = "SELECT n.user_id, n.item_id, n.type " .
               " FROM plugin_docman_notifications AS n " .
               " JOIN plugin_docman_item AS i " .
               " ON n.item_id = i.item_id " .
               " WHERE i.group_id = " . $this->da->quoteSmart($project_id);
        if ($user_id) {
            $sql .= " AND n.user_id = " . $this->da->quoteSmart($user_id);
        }
        $sql .= " ORDER BY user_id";
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function search($user_id, $item_id, $type)
    {
        $sql = sprintf(
            "SELECT * FROM plugin_docman_notifications WHERE user_id = %s AND item_id = %s AND type = %s",
            $this->da->escapeInt($user_id),
            $this->da->escapeInt($item_id),
            $this->da->quoteSmart($type)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchUserIdByObjectIdAndType($item_id, $type)
    {
        $sql = sprintf(
            "SELECT * FROM plugin_docman_notifications WHERE item_id = %s AND type = %s",
            $this->da->escapeInt($item_id),
            $this->da->quoteSmart($type)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return bool
     */
    public function create($user_id, $item_id, $type)
    {
        $sql = sprintf(
            "INSERT INTO plugin_docman_notifications (user_id, item_id, type) VALUES (%s, %s, %s)",
            $this->da->escapeInt($user_id),
            $this->da->escapeInt($item_id),
            $this->da->quoteSmart($type)
        );
        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function delete($user_id, $item_id, $type)
    {
        $sql = sprintf(
            "DELETE FROM plugin_docman_notifications WHERE user_id = %s AND item_id = %s AND type = %s",
            $this->da->escapeInt($user_id),
            $this->da->escapeInt($item_id),
            $this->da->quoteSmart($type)
        );
        return $this->update($sql);
    }

    public function deleteByItemId($item_id)
    {
        $item_id = $this->da->escapeInt($item_id);

        $sql = "DELETE FROM plugin_docman_notifications
                WHERE item_id = $item_id";

        return $this->update($sql);
    }

    public function deleteByItemIdAndUserId($item_id, $user_id)
    {
        $item_id = $this->da->escapeInt($item_id);
        $user_id = $this->da->escapeInt($user_id);

        $sql = "DELETE FROM plugin_docman_notifications
                WHERE item_id = $item_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }
}
