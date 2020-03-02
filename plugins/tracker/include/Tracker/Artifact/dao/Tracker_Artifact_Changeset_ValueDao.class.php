<?php
/**
 * Copyright (c) Enalean SAS, 2017. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_Artifact_Changeset_ValueDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value';
    }

    public function searchById($id)
    {
        $id = $this->da->escapeInt($id);
        $sql = "SELECT * FROM $this->table_name
                WHERE changeset_id = $id";
        return $this->retrieve($sql);
    }

    public function searchByFieldId($changeset_id, $field_id)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id = $this->da->escapeInt($field_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE changeset_id = $changeset_id
                    AND field_id = $field_id";
        return $this->retrieve($sql);
    }

    public function searchByArtifactId($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "SELECT changeset_value.*
                FROM tracker_changeset_value AS changeset_value
                JOIN tracker_changeset AS changeset ON (changeset.id = changeset_value.changeset_id)
                WHERE changeset.artifact_id = $artifact_id";
        $results = array();
        foreach ($this->retrieve($sql) as $row) {
            $results[$row['changeset_id']][] = $row;
        }
        return $results;
    }

    public function save($changeset_id, $field_id, $has_changed)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id = $this->da->escapeInt($field_id);
        $has_changed = $has_changed ? 1 : 0;
        $sql = "INSERT INTO $this->table_name(changeset_id, field_id, has_changed)
                VALUES ($changeset_id, $field_id, $has_changed)";
        return $this->updateAndGetLastId($sql);
    }

    public function createFromLastChangesetByTrackerId($tracker_id, $field_id, $has_changed)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $field_id    = $this->da->escapeInt($field_id);
        $has_changed = $has_changed ? 1 : 0;
        $changesetValueDao = new Tracker_Artifact_ChangesetDao();

        $sql = " INSERT INTO $this->table_name(changeset_id, field_id, has_changed)
                 SELECT C.id as changeset_id, $field_id, 1
                 FROM tracker_changeset AS C
                   INNER JOIN tracker_artifact AS A ON A.id = C.artifact_id
                   INNER JOIN ( SELECT artifact_id, MAX(id) as max_id
                                FROM tracker_changeset
                                GROUP BY artifact_id ) AS C1 ON C.id = C1.max_id
                 WHERE A.tracker_id = $tracker_id ";
        $this->update($sql);
        $sql = " SELECT CV.id as cv
                 FROM tracker_changeset AS C
                   INNER JOIN tracker_artifact AS A ON A.id = C.artifact_id
                   INNER JOIN ( SELECT artifact_id, MAX(id) as max_id
                                FROM tracker_changeset
                                GROUP BY artifact_id ) AS C1 ON C.id = C1.max_id
                   INNER JOIN tracker_changeset_value AS CV ON C.id = CV.changeset_id
                 WHERE A.tracker_id = $tracker_id AND CV.field_id = $field_id AND has_changed = 1";

        $result = $this->retrieve($sql);

        if (! $result) {
            return false;
        }

        $changesetValueIds = [];
        foreach ($result as $row) {
            $changesetValueIds[] = $row['cv'];
        }

        return $changesetValueIds;
    }

    public function delete($changeset_id)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "DELETE
                FROM $this->table_name
                WHERE changeset_id = $changeset_id";
        return $this->update($sql);
    }
}
