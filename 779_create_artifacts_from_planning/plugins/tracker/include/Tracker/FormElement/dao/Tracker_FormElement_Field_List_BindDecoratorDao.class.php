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

class Tracker_FormElement_Field_List_BindDecoratorDao extends DataAccessObject {
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_decorator';
    }
    public function searchByFieldId($field_id) {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }
    public function duplicate($from_field_id, $to_field_id, $value_mapping) {
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_field_id    = $this->da->escapeInt($to_field_id);
        $sql = "INSERT INTO $this->table_name (field_id, value_id, red, green, blue)
                SELECT $to_field_id, value_id, red, green, blue
                FROM $this->table_name
                WHERE field_id = $from_field_id";
        $this->update($sql);
        
        foreach($value_mapping as $from => $to) {
            $from  = $this->da->escapeInt($from);
            $to    = $this->da->escapeInt($to);
            $sql = "UPDATE $this->table_name 
                    SET value_id = $to
                    WHERE field_id = $to_field_id
                      AND value_id = $from";
            $this->update($sql);
        }
    }
    public function save($field_id, $value_id, $red, $green, $blue) {
        $field_id  = $this->da->escapeInt($field_id);
        $value_id  = $this->da->escapeInt($value_id);
        $red       = $this->da->escapeInt($red);
        $green     = $this->da->escapeInt($green);
        $blue      = $this->da->escapeInt($blue);
        
        $sql = "REPLACE INTO $this->table_name (field_id, value_id, red, green, blue)
            SELECT field_id, id, $red, $green, $blue
            FROM tracker_field_list_bind_static_value
            WHERE original_value_id = $value_id OR id = $value_id";
        return $this->update($sql);
    }
    
    public function delete($field_id, $value_id) {
        $field_id  = $this->da->escapeInt($field_id);
        $value_id  = $this->da->escapeInt($value_id);
        $sql = "DELETE FROM $this->table_name 
                WHERE field_id = $field_id
                  AND value_id = $value_id;";
        return $this->update($sql);
    }
}
?>
