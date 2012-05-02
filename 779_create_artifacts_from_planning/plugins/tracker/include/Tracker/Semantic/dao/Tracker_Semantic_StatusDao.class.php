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

require_once('common/dao/include/DataAccessObject.class.php');

class Tracker_Semantic_StatusDao extends DataAccessObject {
    
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_semantic_status';
    }
    
    public function searchByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }
    
    public function save($tracker_id, $field_id, $open_value_ids) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);
        
        //Start to delete all previous entries
        $sql = "DELETE FROM $this->table_name
                WHERE tracker_id = $tracker_id";
        $this->update($sql);
        
        // Now save the new values
        $values = array();
        foreach($open_value_ids as $vid) {
            $vid = $this->da->escapeInt($vid);
            $values[] = "($tracker_id, $field_id, $vid)";
        }
        if ($values = implode(', ', $values)) {
            $sql = "INSERT INTO $this->table_name (tracker_id, field_id, open_value_id) 
                    VALUES $values";
            return $this->update($sql);
        }
        return true;
    }
    
}
?>
