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

class Workflow_Dao extends DataAccessObject
{
    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'tracker_workflow';
    }

    public function create($tracker_id, $field_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id = $this->da->escapeInt($field_id);
        $sql = "INSERT INTO $this->table_name (tracker_id, field_id)
                VALUES ($tracker_id, $field_id)";
        return $this->updateAndGetLastId($sql);
    }

    public function searchById($workflow_id)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE workflow_id = $workflow_id";
        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function updateActivation($workflow_id, $is_used)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $is_used = $this->da->escapeInt($is_used);
        $sql = " UPDATE $this->table_name SET is_used=$is_used WHERE workflow_id=$workflow_id";
        return $this->update($sql);
    }

    public function delete($workflow_id)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $sql = " DELETE FROM $this->table_name WHERE workflow_id=$workflow_id";
        return $this->update($sql);
    }

    public function duplicate($to_tracker_id, $from_id, $to_id, $values, $is_used)
    {
        $to_tracker_id = $this->da->escapeInt($to_tracker_id);
        $to_id = $this->da->escapeInt($to_id);
        $is_used = $this->da->escapeInt($is_used);
        $sql = "INSERT INTO $this->table_name (tracker_id, field_id, is_used)
                VALUES ($to_tracker_id, $to_id, $is_used)";
        return $this->updateAndGetLastId($sql);
    }

    public function save($tracker_id, $field_id, $is_used)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id = $this->da->escapeInt($field_id);
        $is_used = $this->da->escapeInt($is_used);
        $sql = "INSERT INTO $this->table_name (tracker_id, field_id, is_used)
                VALUES ($tracker_id, $field_id, $is_used)";
        return $this->updateAndGetLastId($sql);
    }
}

?>
