<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/dao/include/DataAccessObject.class.php';
require_once 'Docman_ItemDao.class.php';

class Docman_LockDao extends DataAccessObject {

    function __construct(DataAccess $da) {
        parent::__construct($da);
    }

    function searchLockForItem($itemId) {
        $sql = 'SELECT *'.
               ' FROM plugin_docman_item_lock'.
               ' WHERE item_id = '.$this->da->quoteSmart($itemId);
        return $this->retrieve($sql);
    }

    /**
     * Search all locks set on items that belongs to the same project than given
     * item id
     * 
     * @param Integer $itemId
     * @return DataAccessResult
     */
    function searchLocksForProjectByItemId($itemId) {
        $sql = 'SELECT l.item_id, l.user_id'.
               ' FROM plugin_docman_item_lock l'.
               '   JOIN plugin_docman_item i1 ON (i1.item_id = l.item_id)'.
               '   JOIN plugin_docman_item i2 ON (i2.group_id = i1.group_id)'.
               ' WHERE i2.item_id = '.$this->da->quoteSmart($itemId).
               ' AND '.Docman_ItemDao::getCommonExcludeStmt('i1');
        return $this->retrieve($sql);
    }

    /**
    * Search all locks set on items that belong to the same given project. 
    *
    * @param Integer $groupId
    * @return DataAccessResult
    */
    function searchLocksForProjectByGroupId($groupId) {
        $sql = 'SELECT l.*'.
               ' FROM plugin_docman_item_lock l'.
               '   JOIN plugin_docman_item i ON (l.item_id = i.item_id)'.
               ' WHERE i.group_id = '.$this->da->quoteSmart($groupId).
               ' AND '.Docman_ItemDao::getCommonExcludeStmt('i');
        return $this->retrieve($sql);
    }

    /**
     * Retreive lock info for several items.
     * 
     * @param Array $itemIds
     * 
     * @return DataAccessResult
     */
    function searchLocksForItemIds(array $itemIds) {
        $sql = 'SELECT l.item_id, l.user_id'.
               ' FROM plugin_docman_item_lock l'.
               '   JOIN plugin_docman_item i ON (i.item_id = l.item_id)'.
               ' WHERE i.item_id IN ('.implode(',', $itemIds).')'.
               ' AND '.Docman_ItemDao::getCommonExcludeStmt('i');
        return $this->retrieve($sql);
    }
    
    function addLock($itemId, $userId, $date) {
        $sql = 'INSERT INTO plugin_docman_item_lock'.
               ' (item_id, user_id, lock_date)'.
               ' VALUES '.
               '('.$this->da->quoteSmart($itemId).
               ','.$this->da->quoteSmart($userId).
               ','.$this->da->quoteSmart($date).
               ')';
        return $this->update($sql);
    }

    function delLock($itemId) {
        $sql = 'DELETE FROM plugin_docman_item_lock'.
               ' WHERE item_id = '.$this->da->quoteSmart($itemId);
        return $this->update($sql);
    }
}
?>