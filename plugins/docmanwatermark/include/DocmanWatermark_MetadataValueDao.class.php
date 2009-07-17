<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once 'common/dao/include/DataAccessObject.class.php';

/**
 *  Data Access Object for DocmanWatermark_MetataValueDao 
 */
class DocmanWatermark_MetadataValueDao extends DataAccessObject {
    /**
    * Constructs the DocmanWatermark_MetadataValueDao
    * @param $da instance of the DataAccess class
    */
    public function __construct($da) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all values for field
    * @return DataAccessResult
    */
    public function searchByFieldId($field_id) {
        $sql = sprintf("SELECT value_id, watermark FROM plugin_docmanwatermark_metadata_love_md_extension JOIN (plugin_docman_metadata_love_md) USING (value_id) WHERE field_id= %s ",
            $this->da->quoteSmart($field_id));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches DocmanWatermark_MetadataValueDao by value_id 
    * @return DataAccessResult
    */
    public function searchByValueId($value_id) {
        $sql = sprintf("SELECT value_id, watermark FROM plugin_docmanwatermark_metadata_love_md_extension WHERE value_id = %s",
				$this->da->quoteSmart($value_id));
        return $this->retrieve($sql);
    }



    /**
    * update row value in the table plugin_docmanwatermark_metadata_love_md_extension
    * @param wmdvs: array of DocmanWatermark_MetadataValue objects
    * @return void
    */
    public function update($wmdv) {
        $sql = sprintf("INSERT INTO plugin_docmanwatermark_metadata_love_md_extension (value_id, watermark) VALUES (%s, %s)",
                        $this->da->quoteSmart($wmdv->getValueId()),
                        $this->da->quoteSmart($wmdv->getWatermark()));
        $this->retrieve($sql);
    }
    
    /**
    * remove row value in the table plugin_docmanwatermark_metadata_love_md_extension
    * @param groupId: project id
    * @return void
    */
    public function deleteByGroupId($groupId) {
        $sql = sprintf("DELETE FROM plugin_docmanwatermark_metadata_love_md_extension " .
                       "WHERE value_id IN (" .
                       "    SELECT DISTINCT value_id " .
                       "    FROM plugin_docmanwatermark_metadata_extension " .
                       "    JOIN plugin_docman_metadata_love_md USING (field_id) " .
                       "    WHERE group_id = %s" .
                       ")",
               $this->da->quoteSmart($groupId));
        $this->retrieve($sql);        
    }
    
}


?>