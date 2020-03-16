<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 *  Data Access Object for Docman_LogDao
 */
class Docman_LogDao extends DataAccessObject
{
    /**
    * Searches Docman_LogDao by ItemId
    * @return DataAccessResult
    */
    public function searchByItemId($itemId, $order = '')
    {
        $sql = sprintf(
            "SELECT time, group_id, user_id, type, old_value, new_value, field FROM plugin_docman_log WHERE item_id = %s " . $order,
            $this->da->quoteSmart($itemId)
        );
        return $this->retrieve($sql);
    }
    /**
    * Searches Docman_LogDao by ItemId order by time
    * @return DataAccessResult
    */
    public function searchByItemIdOrderByTimestamp($itemId)
    {
        return $this->searchByItemId($itemId, ' ORDER BY time DESC ');
    }

    /**
     * Search in logs if user accessed the given item after the given date.
     */
    public function searchUserAccessSince($userId, $itemId, $date)
    {
        $sql = 'SELECT NULL' .
            ' FROM plugin_docman_log' .
            ' WHERE item_id = ' . $this->da->escapeInt($itemId) .
            ' AND user_id = ' . $this->da->escapeInt($userId) .
            ' AND type = ' . PLUGIN_DOCMAN_EVENT_ACCESS .
            ' AND time > ' . $this->da->escapeInt($date) .
            ' LIMIT 1';
        $dar = $this->retrieve($sql);
        return ($dar && !$dar->isError() && $dar->rowCount() == 1);
    }

    /**
    * create a row in the table plugin_docman_log
    * @return true or id(auto_increment) if there is no error
    */
    public function create($group_id, $item_id, $user_id, $type, $old_value = null, $new_value = null, $field = null)
    {
        $sql = 'INSERT INTO plugin_docman_log (time, group_id, item_id, user_id, type';
        if (!is_null($old_value)) {
            $sql .= ', old_value';
        }
        if (!is_null($new_value)) {
            $sql .= ', new_value';
        }
        if (!is_null($field)) {
            $sql .= ', field';
        }
        $sql .= ') VALUES (' . $this->da->quoteSmart(time()) . ', ' . $this->da->quoteSmart($group_id) . ', ' . $this->da->quoteSmart($item_id) . ', ' . $this->da->quoteSmart($user_id) . ', ' . $this->da->quoteSmart($type) . '';
        if (!is_null($old_value)) {
            $sql .= ', ' . $this->da->quoteSmart($old_value);
        }
        if (!is_null($new_value)) {
            $sql .= ', ' . $this->da->quoteSmart($new_value);
        }
        if (!is_null($field)) {
            $sql .= ', ' . $this->da->quoteSmart($field);
        }
        $sql .= ')';
        $inserted = $this->update($sql);

        return $inserted;
    }

    public function getSqlStatementForLogsDaily($group_id, $logs_cond)
    {
        return 'SELECT log.time AS time, '
               . 'CASE WHEN log.type = 1 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Add')) .
               ' WHEN log.type = 2 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Edit')) .
               ' WHEN log.type = 3 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Move')) .
               ' WHEN log.type = 4 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Delete')) .
               ' WHEN log.type = 5 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Access')) .
               ' WHEN log.type = 11 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Delete version')) .
               ' WHEN log.type = 12 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Restore')) .
               ' WHEN log.type = 13 THEN ' . $this->da->quoteSmart(dgettext('tuleap-docman', 'Restore version')) .
               ' END as type, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(item.item_id," - ",item.title) AS title '
               . ' FROM plugin_docman_log AS log, user, plugin_docman_item AS item '
               . ' WHERE ' . $logs_cond
               . ' AND log.group_id = ' . $this->da->quoteSmart($group_id)
               . ' AND item.item_id = log.item_id '
               . ' AND log.type in (1,2,3,4,5,11,12,13) '
               . ' ORDER BY time DESC ';
    }
}
