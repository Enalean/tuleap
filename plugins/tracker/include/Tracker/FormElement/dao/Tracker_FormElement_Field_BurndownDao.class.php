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
class Tracker_FormElement_Field_BurndownDao extends Tracker_FormElement_SpecificPropertiesDao {
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_burndown';
    }
    
    public function save($field_id, $row) {
        $field_id     = $this->da->escapeInt($field_id);
        $use_capacity = $this->da->escapeInt($row['use_capacity']);
        
        $sql = "REPLACE INTO $this->table_name (field_id, use_capacity)
                VALUES ($field_id, $use_capacity)";
        return $this->retrieve($sql);
    }
    
     public function duplicate($from_field_id, $to_field_id) {
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_field_id    = $this->da->escapeInt($to_field_id);
        $sql = "INSERT INTO $this->table_name (field_id, use_capacity)
                SELECT $to_field_id, use_capacity
                FROM $this->table_name
                WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
?>
