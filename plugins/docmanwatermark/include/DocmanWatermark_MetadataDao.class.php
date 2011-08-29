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

/**
 *  Data Access Object for DocmanWatermark_MetataDao 
 */
class DocmanWatermark_MetadataDao extends DataAccessObject {

    /**
    * Searches Docmanwatermark_MetadataDao by group_id 
    * @return DataAccessResult
    */
    public function searchByGroupId($group_id) {
        $sql = sprintf("SELECT field_id FROM plugin_docmanwatermark_metadata_extension WHERE group_id = %s",
                $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);
    }

    public function updateByGroupId($group_id, $field_id) {
        $sql = sprintf("UPDATE plugin_docmanwatermark_metadata_extension SET field_id = %s WHERE group_id = %s",
                $this->da->quoteSmart($field_id),
                $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);        
    }

    /**
    * create a row in the table plugin_docmanwatermark_metadata_extension 
    * @return 
    */
    public function createByGroupId($group_id, $field_id) {
        $sql = sprintf("INSERT INTO plugin_docmanwatermark_metadata_extension (group_id, field_id) VALUES (%s, %s)",
                $this->da->quoteSmart($group_id),
                $this->da->quoteSmart($field_id));
        return $this->retrieve($sql);
    }

    public function deleteByGroupId($group_id) {
        $sql = sprintf("DELETE FROM plugin_docmanwatermark_metadata_extension WHERE group_id = %s",
                $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);        
    }
    
    public function searchNameByFieldId($field_id) {
        $sql = sprintf("SELECT name FROM plugin_docman_metadata WHERE field_id = %s",
                $this->da->quoteSmart($field_id));
        return $this->retrieve($sql);                
    }
    
}


?>