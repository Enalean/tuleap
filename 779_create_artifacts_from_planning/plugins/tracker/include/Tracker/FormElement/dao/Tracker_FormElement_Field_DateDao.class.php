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

require_once('Tracker_FormElement_SpecificPropertiesDao.class.php');
/**
 *  Data Access Object for Tracker_FormElement_Field 
 */
class Tracker_FormElement_Field_DateDao extends Tracker_FormElement_SpecificPropertiesDao {
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_date';
    }
    
    public function save($field_id, $row) {
        $field_id  = $this->da->escapeInt($field_id);
        
        if (isset($row['default_value'])) {
            if (is_numeric($row['default_value'])) {
                $default_value = $this->da->escapeInt($row['default_value']);
            } else {
                $default_value = $this->da->escapeInt(strtotime($row['default_value']));
            }
        } else {
            $default_value = "NULL";
        }
        
        if (isset($row['default_value_type'])) {
            $default_value_type = $this->da->escapeInt($row['default_value_type']);
        } else {
            $default_value_type = isset($row['default_value']) ? 1 : 0;
        }
        
        $sql = "REPLACE INTO $this->table_name (field_id, default_value, default_value_type)
                VALUES ($field_id, $default_value, $default_value_type)";
        return $this->update($sql);
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
        
        $sql = "REPLACE INTO $this->table_name (field_id, default_value, default_value_type)
                SELECT $to_field_id, default_value, default_value_type 
                FROM $this->table_name 
                WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
?>