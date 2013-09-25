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

    /**
     * Retrieve the list of artifact id corresponding to a last update date having a specific value
     *
     * @param Integer $trackerId Tracker id
     * @param Integer $date      Last update date
     *
     * @return DataAccessResult
     */
    public function getArtifactsByFieldAndLastUpdateDate($trackerId, $date) {
        $trackerId  = $this->da->escapeInt($trackerId);
        $date       = $this->da->escapeInt($date);
        $halfDay    = 60 * 60 * 12;
        $minDate    = $date - $halfDay;
        $maxDate    = $date + $halfDay;
        $sql        = "SELECT MAX(c.id) AS id, c.artifact_id FROM
                         tracker_changeset c
                         JOIN tracker_artifact a ON c.artifact_id = a.id
                         WHERE DATE(FROM_UNIXTIME(c.submitted_on)) BETWEEN DATE(FROM_UNIXTIME(".$minDate.")) AND DATE(FROM_UNIXTIME(".$maxDate."))
                           AND a.tracker_id = ".$trackerId."
                         GROUP BY c.artifact_id";
        return $this->retrieve($sql);
    }

    /**
     * We need both artifact_id and changset_id so we ensure we fetch the changeset of an artifact we are allowed to see
     *
     * @param Integer $artifact_id
     * @param Integer $changeset_id
     *
     * @return DataAccessResult
     */
    public function searchChangesetNewerThan($artifact_id, $changeset_id) {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $changeset_id = $this->da->escapeInt($changeset_id);

        $sql = "SELECT c_new.*
                FROM tracker_changeset c_new
                    JOIN tracker_changeset c_ref ON (c_ref.artifact_id = c_new.artifact_id)
                WHERE c_ref.id = $changeset_id
                    AND c_ref.artifact_id = $artifact_id
                    AND c_new.id > c_ref.id
                ORDER BY c_new.id ASC";
        return $this->retrieve($sql);
    }
}

?>