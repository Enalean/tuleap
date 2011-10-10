<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class Transition_PostAction_Field_DateDao extends DataAccessObject {

    public function create($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "INSERT INTO tracker_workflow_transition_postactions_field_date 
                (transition_id) 
                VALUES 
                ($transition_id)";
        return $this->update($sql);
    }
    
    public function updatePostAction($id, $field_id, $value_type) {
        $id         = $this->da->escapeInt($id);
        $field_id   = $this->da->escapeInt($field_id);
        $value_type = $this->da->escapeInt($value_type);
        $sql = "UPDATE tracker_workflow_transition_postactions_field_date 
                SET field_id   = $field_id, 
                    value_type = $value_type
                WHERE id = $id";
        return $this->update($sql);
    }
    
    public function searchByTransitionId($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "SELECT * 
                FROM tracker_workflow_transition_postactions_field_date 
                WHERE transition_id = $transition_id
                ORDER BY id";
        return $this->retrieve($sql);
    }
    
    public function searchByFieldId($transition_id, $field_id) {
        $field_id      = $this->da->escapeInt($field_id);
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "SELECT * 
                FROM tracker_workflow_transition_postactions_field_date 
                WHERE field_id      = $field_id 
                  AND transition_id = $transition_id
                ORDER BY id";
        return $this->retrieve($sql);
    }
    
    public function deletePostAction($id) {
        $id = $this->da->escapeInt($id);
        $sql = "DELETE 
                FROM tracker_workflow_transition_postactions_field_date 
                WHERE id = $id";
        return $this->update($sql);
    }
}
?>
