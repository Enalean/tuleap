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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Base class for field post action DAOs.
 */
class Transition_PostAction_CIBuildDao extends DataAccessObject {

    /*
     * Transition_PostAction_Jenkins_Build table before made the forge upgrade script
     *
     *  CREATE TABLE tracker_workflow_transition_postactions_jenkins_build (
     *      id ,
     *      transition_id,
     *      host,
     *      job_name,
     *  )
     *
     */
    public function __construct() {
        parent::__construct();
        $this->table_name        = 'tracker_workflow_transition_postactions_cibuild';
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
        
        $sql = "INSERT INTO $this->table_name (transition_id) 
                VALUES ($transition_id)";
        
        return $this->updateAndGetLastId($sql);
    }

    public function searchByTransitionId($transition_id) {
        $transition_id = $this->da->escapeInt($transition_id);
        
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE transition_id = $transition_id
                ORDER BY id";
        
        return $this->retrieve($sql);
    }
    
        /**
     * Update postaction entry
     *
     * @param int   $id       The id of the postaction 
     * @param mixed $value    The job url.
     *
     * @return bool true if success false otherwise
     */
    public function updatePostAction($id, $value) {
        $id       = $this->da->escapeInt($id);
        $field_id = $this->da->escapeInt($field_id);
        $value    = $this->da->quoteSmart($value);
        
        $sql = "UPDATE $this->table_name
                SET job_url = $value
                WHERE id = $id";
        return $this->update($sql);
    }
    
    public function deletePostActionsByWorkflowId() {

    }

    public function save() {

    }

}

?>
