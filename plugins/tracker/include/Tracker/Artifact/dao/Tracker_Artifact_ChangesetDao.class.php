<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

class Tracker_Artifact_ChangesetDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset';
    }

    public function searchByArtifactId($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE artifact_id = $artifact_id
                ORDER BY id";
        return $this->retrieve($sql);
    }

    public function searchByArtifactIdAndChangesetId($artifact_id, $changeset_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE artifact_id = $artifact_id
                  AND id = $changeset_id";
        return $this->retrieve($sql);
    }

    public function searchLastChangesetByArtifactId($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "SELECT c.* FROM tracker_changeset c
                JOIN tracker_artifact AS a on (a.last_changeset_id = c.id)
                WHERE a.id = $artifact_id";

        return $this->retrieve($sql);
    }

    public function searchLastChangesetAndValueForArtifactField($artifact_id, $field_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id = $this->da->escapeInt($field_id);
        $sql = "SELECT cs.id AS id, cs.submitted_by, cs.submitted_on, cs.email, cv.id AS value_id, cv.has_changed
                FROM tracker_artifact        AS a
                JOIN tracker_changeset       AS cs ON (cs.id = a.last_changeset_id)
                JOIN tracker_changeset_value AS cv ON (cv.changeset_id = a.last_changeset_id)
                WHERE a.id = $artifact_id
                    AND field_id = $field_id";
        return $this->retrieve($sql);
    }

    public function searchPreviousChangesetAndValueForArtifactField($artifact_id, $field_id, $changeset_id)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $field_id     = $this->da->escapeInt($field_id);
        $changeset_id = $this->da->escapeInt($changeset_id);

        $sql = "SELECT cs.id AS id, cs.submitted_by, cs.submitted_on, cs.email, cv.id AS value_id, cv.has_changed
                FROM tracker_artifact        AS a
                JOIN tracker_changeset       AS cs ON (cs.artifact_id = a.id)
                JOIN tracker_changeset_value AS cv ON (cs.id = cv.changeset_id)
                WHERE a.id = $artifact_id
                    AND field_id = $field_id
                    AND cs.id != $changeset_id
                ORDER BY cs.id DESC";

        return $this->retrieveFirstRow($sql);
    }

    public function create($artifact_id, $submitted_by, $email, $submitted_on)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $submitted_by = $this->da->escapeInt($submitted_by);
        if (!$submitted_by) {
            $submitted_by = 'NULL';
        }
        $submitted_on = $this->da->escapeInt($submitted_on);
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

    public function delete($changeset_id)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "DELETE
                FROM $this->table_name
                WHERE id = $changeset_id";
        return $this->update($sql);
    }

    /**
     * Retrieve the list of artifact id corresponding to a last update date having a specific value
     *
     * @param int $trackerId Tracker id
     * @param int $date Last update date
     *
     * @return DataAccessResult
     */
    public function getArtifactsByFieldAndLastUpdateDate($trackerId, $date)
    {
        $trackerId  = $this->da->escapeInt($trackerId);
        $date       = $this->da->escapeInt($date);
        $halfDay    = 60 * 60 * 12;
        $minDate    = $date - $halfDay;
        $maxDate    = $date + $halfDay;
        $sql        = "SELECT MAX(c.id) AS id, c.artifact_id FROM
                         tracker_changeset c
                         JOIN tracker_artifact a ON c.artifact_id = a.id
                         WHERE DATE(FROM_UNIXTIME(c.submitted_on)) BETWEEN DATE(FROM_UNIXTIME(" . $minDate . ")) AND DATE(FROM_UNIXTIME(" . $maxDate . "))
                           AND a.tracker_id = " . $trackerId . "
                         GROUP BY c.artifact_id";
        return $this->retrieve($sql);
    }

    /**
     * We need both artifact_id and changset_id so we ensure we fetch the changeset of an artifact we are allowed to see
     *
     * @param int $artifact_id
     * @param int $changeset_id
     *
     * @return DataAccessResult
     */
    public function searchChangesetNewerThan($artifact_id, $changeset_id)
    {
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

    /**
     * @see http://stackoverflow.com/questions/2111384/sql-join-selecting-the-last-records-in-a-one-to-many-relationship
     */
    public function searchChangesetByTimestamp($artifact_id, $timestamp)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $timestamp    = $this->da->escapeInt($timestamp);

        $sql = "SELECT changeset1.*
                    FROM tracker_changeset       AS changeset1
                    LEFT JOIN  tracker_changeset AS changeset2
                    ON (
                        changeset2.artifact_id = $artifact_id
                        AND changeset1.id < changeset2.id
                        AND changeset2.submitted_on <= $timestamp
                    )
                    WHERE changeset2.id IS NULL
                    AND changeset1.artifact_id = $artifact_id
                    AND changeset1.submitted_on <= $timestamp";

        return $this->retrieve($sql);
    }

    /**
     * @return int
     */
    public function countChangesets()
    {
        $sql = 'SELECT COUNT(id) AS nb FROM tracker_changeset';

        $dar = $this->retrieve($sql);
        if ($dar === false) {
            return 0;
        }
        $row = $dar->getRow();

        return (int) $row['nb'];
    }
}
