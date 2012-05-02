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


require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Docman_LogDao 
 */
class Docman_LogDao extends DataAccessObject {
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM plugin_docman_log";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Docman_LogDao by Timestamp 
    * @return DataAccessResult
    */
    function searchByTimestamp($time) {
        $sql = sprintf("SELECT group_id, item_id, user_id, type, old_value, new_value FROM plugin_docman_log WHERE time = %s",
				$this->da->quoteSmart($time));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_LogDao by ItemId 
    * @return DataAccessResult
    */
    function searchByItemId($itemId, $order = '') {
        $sql = sprintf("SELECT time, group_id, user_id, type, old_value, new_value, field FROM plugin_docman_log WHERE item_id = %s ".$order,
				$this->da->quoteSmart($itemId));
        return $this->retrieve($sql);
    }
    /**
    * Searches Docman_LogDao by ItemId order by time
    * @return DataAccessResult
    */
    function searchByItemIdOrderByTimestamp($itemId) {
        return $this->searchByItemId($itemId, ' ORDER BY time DESC ');
    }

    /**
    * Searches Docman_LogDao by UserId 
    * @return DataAccessResult
    */
    function searchByUserId($userId) {
        $sql = sprintf("SELECT time, group_id, item_id, type, old_value, new_value FROM plugin_docman_log WHERE user_id = %s",
				$this->da->quoteSmart($userId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_LogDao by Type 
    * @return DataAccessResult
    */
    function searchByType($type) {
        $sql = sprintf("SELECT time, group_id, item_id, user_id, old_value, new_value FROM plugin_docman_log WHERE type = %s",
				$this->da->quoteSmart($type));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_LogDao by OldValue 
    * @return DataAccessResult
    */
    function searchByOldValue($oldValue) {
        $sql = sprintf("SELECT time, group_id, item_id, user_id, type, new_value FROM plugin_docman_log WHERE old_value = %s",
				$this->da->quoteSmart($oldValue));
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_LogDao by NewValue 
    * @return DataAccessResult
    */
    function searchByNewValue($newValue) {
        $sql = sprintf("SELECT time, group_id, item_id, user_id, type, old_value FROM plugin_docman_log WHERE new_value = %s",
				$this->da->quoteSmart($newValue));
        return $this->retrieve($sql);
    }

    /**
     * Search in logs if user accessed the given item after the given date.
     */
    function searchUserAccessSince($userId, $itemId, $date) {
        $sql = 'SELECT NULL'.
            ' FROM plugin_docman_log'.
            ' WHERE item_id = '.$this->da->escapeInt($itemId).
            ' AND user_id = '.$this->da->escapeInt($userId).
            ' AND type = '.PLUGIN_DOCMAN_EVENT_ACCESS.
            ' AND time > '.$this->da->escapeInt($date).
            ' LIMIT 1';
        $dar = $this->retrieve($sql);
        return ($dar && !$dar->isError() && $dar->rowCount() == 1);
    }

    /**
    * create a row in the table plugin_docman_log 
    * @return true or id(auto_increment) if there is no error
    */
    function create($group_id, $item_id, $user_id, $type, $old_value = null, $new_value = null, $field = null) {
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
        $sql .= ') VALUES ('. $this->da->quoteSmart(time()) .', '. $this->da->quoteSmart($group_id) .', '. $this->da->quoteSmart($item_id) .', '. $this->da->quoteSmart($user_id) .', '. $this->da->quoteSmart($type) .'';
        if (!is_null($old_value)) {
            $sql .= ', '. $this->da->quoteSmart($old_value);
        }
        if (!is_null($new_value)) {
            $sql .= ', '. $this->da->quoteSmart($new_value);
        }
        if (!is_null($field)) {
            $sql .= ', '. $this->da->quoteSmart($field);
        }
        $sql .= ')';
        $inserted = $this->update($sql);
        
        return $inserted;
    }
    function getSqlStatementForLogsDaily($group_id, $logs_cond) {
        return 'SELECT log.time AS time, '
               .'CASE WHEN log.type = 1 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','action_add')).
               ' WHEN log.type = 2 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','action_edit')).
               ' WHEN log.type = 3 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','action_move')).
               ' WHEN log.type = 4 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','action_delete')).
               ' WHEN log.type = 5 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','action_access')).
               ' WHEN log.type = 11 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','action_delete_version')).
               ' WHEN log.type = 12 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','event_restore')).
               ' WHEN log.type = 13 THEN '.$this->da->quoteSmart($GLOBALS['Language']->getText('plugin_docman','event_restore_version')).
               ' END as type, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(item.item_id," - ",item.title) AS title '
               .' FROM plugin_docman_log AS log, user, plugin_docman_item AS item '
               .' WHERE '. $logs_cond
               .' AND log.group_id = '. $this->da->quoteSmart($group_id)
               .' AND item.item_id = log.item_id '
               .' AND log.type in (1,2,3,4,5,11,12,13) '
               .' ORDER BY time DESC ';
    }
}


?>