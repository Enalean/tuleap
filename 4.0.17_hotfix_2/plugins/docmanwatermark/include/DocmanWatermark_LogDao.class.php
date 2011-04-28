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

class DocmanWatermark_LogDao extends DataAccessObject {

    public function __construct($da) {
        DataAccessObject::DataAccessObject($da);
    }

    function searchByItemId($itemId) {
        $sql = 'SELECT * FROM plugin_docmanwatermark_item_excluded_log '.
               ' WHERE item_id = '.$itemId.
               ' ORDER BY time DESC';
        return $this->retrieve($sql);
    }
    
    function logEvent($itemId, $userId, $watermarked) {
        $sql = 'INSERT INTO plugin_docmanwatermark_item_excluded_log'.
               '(item_id, time, who, watermarked) VALUES '.
               '('.$this->da->quoteSmart($itemId).
               ','.$this->da->quoteSmart($_SERVER['REQUEST_TIME']).
               ','.$this->da->quoteSmart($userId).
               ','.$this->da->quoteSmart($watermarked).
               ')';
        $res = $this->update($sql);
        if($res && $this->da->affectedRows() == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    function enableWatermarking($itemId, $userId) {
        return $this->logEvent($itemId, $userId, 1);
    }
    
    function disableWatermarking($itemId, $userId) {
        return $this->logEvent($itemId, $userId, 0);
    }

}

?>