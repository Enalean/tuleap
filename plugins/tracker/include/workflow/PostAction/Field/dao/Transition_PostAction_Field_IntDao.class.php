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

class Transition_PostAction_Field_IntDao extends DataAccessObject {
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_workflow_transition_postactions_field_int';
    }
    
    /**
     * Create a new postaction entry
     *
     * @param int $transition_id The transition the post action belongs to
     *
     * @return bool true if success false otherwise
     */
    public function create($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "INSERT INTO tracker_workflow_transition_postactions_field_int
                (transition_id) 
                VALUES 
                ($transition_id)";
        return $this->updateAndGetLastId($sql);
    }
    
        /**
     * Search all postactions belonging to a transition
     *
     * @param int $transition_id The id of the transition 
     *
     * @return DataAccessResult
     */
    public function searchByTransitionId($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "SELECT * 
                FROM tracker_workflow_transition_postactions_field_int
                WHERE transition_id = $transition_id
                ORDER BY id";
        return $this->retrieve($sql);
    }
    
    public function updatePostAction($id, $field_id, $value) {
        $id       = $this->da->escapeInt($id);
        $field_id = $this->da->escapeInt($field_id);
        $value    = $this->da->escapeInt($value);
        
        $sql = <<<SQL
            UPDATE $this->table_name
            SET field_id = $field_id, value = $value
            WHERE id = $id
SQL;
        return $this->update($sql);
    }
}
?>
