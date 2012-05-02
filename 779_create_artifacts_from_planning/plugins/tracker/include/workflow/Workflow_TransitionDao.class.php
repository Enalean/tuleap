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

class Workflow_TransitionDao extends DataAccessObject {
    public function __construct($da = null) {
        parent::__construct($da);
        $this->table_name = 'tracker_workflow_transition';
    }
    
    public function addTransition($workflow_id, $from, $to) {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $to   = $this->da->escapeInt($to);
        $from   = $this->da->escapeInt($from);
        $sql = "INSERT INTO $this->table_name (workflow_id, from_id, to_id)
                VALUES ($workflow_id, $from, $to)";
        return $this->updateAndGetLastId($sql);
    }
    
    public function deleteTransition($workflow_id, $from, $to) {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $from = $this->da->escapeInt($from);
        $to = $this->da->escapeInt($to);
        $sql = " DELETE FROM $this->table_name WHERE from_id=$from AND to_id=$to AND workflow_id=$workflow_id";
        return $this->update($sql);
    }
    
    public function deleteWorkflowTransitions($workflow_id) {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $sql = " DELETE FROM $this->table_name WHERE workflow_id=$workflow_id";
        return $this->update($sql);
    }
    
    public function searchByWorkflow($workflow_id) {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE workflow_id=$workflow_id";
        return $this->retrieve($sql);
    }
    
    public function searchByFromTo($from, $to) { 
        $from = $this->da->escapeInt($from);
        $to = $this->da->escapeInt($to);
        $sql = "SELECT * FROM $this->table_name
                WHERE from_id=$from
                AND to_id=$to";
        return $this->retrieve($sql);
    }
    
    public function searchTransitionId($workflow_id, $from, $to) {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $from = $this->da->escapeInt($from);
        $to = $this->da->escapeInt($to);
        $sql = "SELECT * FROM $this->table_name
                WHERE workflow_id=$workflow_id
                AND from_id=$from
                AND to_id=$to";
        return $this->retrieve($sql);
    }

    
    public function getWorkflowId($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "SELECT workflow_id FROM $this->table_name
                WHERE transition_id=$transition_id";
        return $this->retrieve($sql);
    }
    
    public function searchById($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE transition_id=$transition_id";
        return $this->retrieve($sql);
    }
    
}
?>
