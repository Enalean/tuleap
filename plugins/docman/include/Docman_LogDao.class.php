<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Docman_LogDao 
 */
class Docman_LogDao extends DataAccessObject {
    /**
    * Constructs the Docman_LogDao
    * @param $da instance of the DataAccess class
    */
    function Docman_LogDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
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
    function getSqlStatementForLogsDaily($group_id, $logs_cond, $type) {
        return 'SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, item.title AS title '
        .' FROM plugin_docman_log AS log, user, plugin_docman_item AS item '
        .' WHERE '. $logs_cond
        .' AND log.group_id = '. $this->da->quoteSmart($group_id)
        .' AND item.item_id = log.item_id '
        .' AND type = '. $this->da->quoteSmart($type)
        .' ORDER BY time DESC ';
    }
}


?>