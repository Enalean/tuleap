<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Data Access Object for Tracker_FormElement_Field 
 */
class Tracker_FormElement_Field_ComputedDao extends Tracker_FormElement_SpecificPropertiesDao {
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_computed';
    }
    
    public function save($field_id, $row) {
        $field_id  = $this->da->escapeInt($field_id);
        
        $target_field_name = '';
        if (isset($row['target_field_name'])) {
            $target_field_name = $this->da->quoteSmart($row['target_field_name']);
        }

        $sql = "REPLACE INTO $this->table_name (field_id, target_field_name)
                VALUES ($field_id, $target_field_name)";
        return $this->retrieve($sql);
    }
    
    /**
     * Duplicate specific properties of field
     *
     * @param int $from_field_id the field id source
     * @param int $to_field_id   the field id target
     *
     * @return boolean true if ok, false otherwise
     */
    public function duplicate($from_field_id, $to_field_id) {
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_field_id  = $this->da->escapeInt($to_field_id);
        
        $sql = "REPLACE INTO $this->table_name (field_id, target_field_name)
                SELECT $to_field_id, target_field_name FROM $this->table_name WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
?>
