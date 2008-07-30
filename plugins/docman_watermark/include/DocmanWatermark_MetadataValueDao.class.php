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

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for DocmanWatermark_MetataValueDao 
 */
class DocmanWatermark_MetadataValueDao extends DataAccessObject {
    /**
    * Constructs the DocmanWatermark_MetadataValueDao
    * @param $da instance of the DataAccess class
    */
    function DocmanWatermark_MetadataValueDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM plugin_docmanwatermark_metadata_love_extension";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Docmanwatermark_MetadataValueDao by value_id 
    * @return DataAccessResult
    */
    function searchByValueId($value_id) {
        $sql = sprintf("SELECT value_id, watermark FROM plugin_docmanwatermark_metadata_love_extension WHERE value_id = %s",
				$this->da->quoteSmart($value_id));
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table plugin_docmanwatermark_metadata_love_extension 
    * @return true or id(auto_increment) if there is no error
    */
    function create($value_id, $watermark) {
		$sql = sprintf("INSERT INTO plugin_docmanwatermark_metadata_love_extension (value_id, watermark VALUES (%s, %s)",
				$this->da->quoteSmart($value_id),
				$this->da->quoteSmart($watermark));
        return $this->_createAndReturnId($sql);
    }

    function createFromRow($row) {
        $arg    = array();
        $values = array();
        $cols   = array('value_id', 'watermark');
        foreach ($row as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO plugin_docmanwatermark_Metadata_love_extension '
                .'('.implode(', ', $arg).')'
                .' VALUES ('.implode(', ', $values).')';
            return $this->_createAndReturnId($sql);
        } else {
            return false;
        }
    }

}


?>