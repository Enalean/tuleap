<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/NotificationsDao.class.php');

/**
 *  Data Access Object for Docman Notifications 
 */
class Docman_NotificationsDao extends NotificationsDao {

    function searchDocmanMonitoredItems($groupId, $userId) {
        $sql = "SELECT n.user_id, n.object_id, n.type ".
               " FROM notifications AS n ".
               " JOIN plugin_docman_item AS i ".
               " ON n.object_id = i.item_id ".
               " WHERE i.group_id = ".$this->da->quoteSmart($groupId);
        if ($userId) {
            $sql .= " AND n.user_id = ".$this->da->quoteSmart($userId);
        }
        $sql .= " ORDER BY user_id";
        return $this->retrieve($sql);
    }

}

?>