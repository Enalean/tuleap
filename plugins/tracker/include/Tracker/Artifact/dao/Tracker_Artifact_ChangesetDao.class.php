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

class Tracker_Artifact_ChangesetDao extends DataAccessObject {
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_changeset';
    }   

    public function searchByArtifactId($artifact_id) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE artifact_id = $artifact_id
                ORDER BY id";
        return $this->retrieve($sql);
    }
    
    public function searchByArtifactIdAndChangesetId($artifact_id, $changeset_id) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE artifact_id = $artifact_id
                  AND id = $changeset_id";
        return $this->retrieve($sql);
    }
    
    public function create($artifact_id, $submitted_by, $email) {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $submitted_by = $this->da->escapeInt($submitted_by);
        if (!$submitted_by) {
            $submitted_by = 'NULL';
        }
        $submitted_on = $this->da->escapeInt($_SERVER['REQUEST_TIME']);
        $email        = $email ? $this->da->quoteSmart($email) : 'NULL';
        $sql = "INSERT INTO $this->table_name (artifact_id, submitted_by, submitted_on, email)
                VALUES ($artifact_id, $submitted_by, $submitted_on, $email)";
        if ($changeset_id = $this->updateAndGetLastId($sql)) {
            $uql = "UPDATE tracker_artifact
                    SET last_changeset_id = $changeset_id
                    WHERE id = $artifact_id";
            $this->update($uql);
        }
        return $changeset_id;
    }
    
    public function delete($changeset_id) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "DELETE
                FROM $this->table_name
                WHERE id = $changeset_id";
        return $this->update($sql);
    }
}
?>
