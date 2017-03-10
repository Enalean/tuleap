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

class Dao extends DataAccessObject
{
    public function searchDocmanMonitoredItems($project_id, $user_id)
    {
        $sql = "SELECT n.user_id, n.object_id, n.type ".
               " FROM notifications AS n ".
               " JOIN plugin_docman_item AS i ".
               " ON n.object_id = i.item_id ".
               " WHERE i.group_id = ".$this->da->quoteSmart($project_id);
        if ($user_id) {
            $sql .= " AND n.user_id = ".$this->da->quoteSmart($user_id);
        }
        $sql .= " ORDER BY user_id";
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function search($user_id, $object_id, $type)
    {
        $sql = sprintf(
            "SELECT user_id, object_id, type FROM notifications WHERE user_id = %s AND object_id = %s AND type = %s",
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($object_id),
            $this->da->quoteSmart($type)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchByUserId($user_id)
    {
        $sql = sprintf(
            "SELECT user_id, object_id, type FROM notifications WHERE user_id = %s",
            $this->da->quoteSmart($user_id)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchByObjectId($object_id)
    {
        $sql = sprintf(
            "SELECT user_id, object_id, type FROM notifications WHERE object_id = %s",
            $this->da->quoteSmart($object_id)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchUserIdByObjectIdAndType($object_id, $type)
    {
        $sql = sprintf(
            "SELECT user_id, object_id, type FROM notifications WHERE object_id = %s AND type = %s",
            $this->da->quoteSmart($object_id),
            $this->da->quoteSmart($type)
        );
        return $this->retrieve($sql);
    }

    /**
     * @return bool
     */
    public function create($user_id, $object_id, $type)
    {
        $sql = sprintf(
            "INSERT INTO notifications (user_id, object_id, type) VALUES (%s, %s, %s)",
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($object_id),
            $this->da->quoteSmart($type)
        );
        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function delete($user_id, $object_id, $type)
    {
        $sql = sprintf(
            "DELETE FROM notifications WHERE user_id = %s AND object_id = %s AND type = %s",
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($object_id),
            $this->da->quoteSmart($type)
        );
        return $this->update($sql);
    }
}
