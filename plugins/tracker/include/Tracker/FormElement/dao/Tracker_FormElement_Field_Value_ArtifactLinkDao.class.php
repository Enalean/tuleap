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


class Tracker_FormElement_Field_Value_ArtifactLinkDao extends Tracker_FormElement_Field_ValueDao {
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_artifactlink';
    }
    
    function searchById($changeset_value_id) {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $sql = "SELECT cv.*, a.tracker_id, a.last_changeset_id
                FROM $this->table_name AS cv
                    INNER JOIN tracker_artifact AS a ON (a.id = cv.artifact_id)
                    INNER JOIN tracker_artifact_priority ON (tracker_artifact_priority.curr_id = a.id)
                WHERE changeset_value_id = $changeset_value_id
                ORDER BY tracker_artifact_priority.rank";
        return $this->retrieve($sql);
    }
    
    public function create($changeset_value_id, $artifact_id, $keyword, $group_id) {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $artifact_id        = $this->da->escapeInt($artifact_id);
        $keyword            = $this->da->quoteSmart($keyword);
        $group_id           = $this->da->escapeInt($group_id);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, artifact_id, keyword, group_id)
                VALUES ($changeset_value_id, $artifact_id, $keyword, $group_id)";
        return $this->update($sql);
    }
    
    public function keep($from, $to) {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, artifact_id, keyword, group_id)
                SELECT $to, artifact_id, keyword, group_id
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }

    public function createNoneValue($tracker_id, $field_id) {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ( $changeset_value_ids === false ) {
            return false;
        }
        // $sql = " INSERT INTO $this->table_name(changeset_value_id, value)
        //         VALUES
        //          ( ".implode(' , NULL ),'."\n".' ( ', $changeset_value_ids).", NULL)";
        //return $this->update($sql);
        return true;
    }

    public function updateItemName ($group_id, $oldKeyword, $keyword) {
        $group_id = $this->da->quoteSmart($group_id);
        $keyword= $this->da->quoteSmart($keyword);
        $oldKeyword= $this->da->quoteSmart($oldKeyword);
        $sql = "UPDATE $this->table_name SET 
			keyword=$keyword
            WHERE keyword=$oldKeyword AND group_id=$group_id";
        return $this->update($sql);
    }

    public function deleteReference($artifact_id) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "DELETE FROM $this->table_name
                WHERE artifact_id = $artifact_id";
        return $this->update($sql);
    }
}
?>
