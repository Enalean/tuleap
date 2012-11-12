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
 * Class for field condition DAOs.
 */
class Transition_Condition_FieldNotEmptyDao extends DataAccessObject {
    
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_workflow_transition_condition_field_notempty';
    }
    
    /**
     * Create a new entry
     *
     * @param int $transition_id The transition the post action belongs to
     *
     * @return bool true if success false otherwise
     */
    public function create($transition_id, $field_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $field_id = $this->da->escapeInt($field_id);
        
        $sql = "INSERT INTO $this->table_name (`transition_id`, `field_id`) 
                VALUES ($transition_id, $field_id)";
        
        return $this->updateAndGetLastId($sql);
    }
    
    /**
     * Search all fieldnotempty conditions belonging to a transition
     *
     * @param int $transition_id The id of the transition 
     *
     * @return DataAccessResult
     */
    public function searchByTransitionId($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE transition_id = $transition_id
                ORDER BY id";
        
        return $this->retrieve($sql);
    }
    

}
?>
