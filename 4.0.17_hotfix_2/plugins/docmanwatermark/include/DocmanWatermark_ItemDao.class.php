<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * 
 */

require_once 'common/dao/include/DataAccessObject.class.php';
require_once dirname(__FILE__).'/../../docman/include/Docman_ItemDao.class.php';

class DocmanWatermark_ItemDao extends DataAccessObject {

    /**
     * Constructs DocmanWatermark_ItemDao
     * @param DataAccess $da DataAccess class
     */
    public function __construct($da) {
        parent::__construct($da);
    }
    
    public function searchAllItemsNotWatermarked($groupId) {
        $sql = 'SELECT i.item_id, i.title, excluded_log.time'.
               ' FROM plugin_docman_item i'.
               ' INNER JOIN plugin_docmanwatermark_item_excluded excluded USING (item_id)'.
               ' INNER JOIN plugin_docmanwatermark_item_excluded_log excluded_log USING (item_id)'.
               ' WHERE i.group_id = '.$this->da->quoteSmart($groupId).
               ' AND '.Docman_ItemDao::getCommonExcludeStmt('i').
               ' ORDER BY i.item_id DESC, excluded_log.time DESC';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return false;
    }
    
    public function searchItemNotWatermarked($itemId) {
        $sql = 'SELECT NULL FROM plugin_docmanwatermark_item_excluded WHERE item_id = '.$this->da->quoteSmart($itemId);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        }
        return false;
    }
    
    public function enableWatermarking($itemId) {
        $sql = 'DELETE FROM plugin_docmanwatermark_item_excluded WHERE item_id = '.$this->da->quoteSmart($itemId);
        if ($this->update($sql)) {
            return ($this->da->affectedRows() == 1);
        }
        return false;
    }
    
    public function disableWatermarking($itemId) {
        $sql = 'INSERT INTO plugin_docmanwatermark_item_excluded (item_id) VALUES ('.$this->da->quoteSmart($itemId).')';
        if ($this->update($sql)) {
            return ($this->da->affectedRows() == 1);
        }
        return false;
    }
    
}
?>