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

class Tracker_Artifact_Changeset_CommentDao extends DataAccessObject {
    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_changeset_comment';
    }
    
    public function searchLastVersion($changeset_id) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE changeset_id = $changeset_id
                ORDER BY id DESC
                LIMIT 1";
        return $this->retrieve($sql);
    }

    public function createNewVersion($changeset_id, $body, $submitted_by, $submitted_on, $parent_id, $body_format) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $body         = $this->da->quoteSmart($body);
        $submitted_by = $this->da->escapeInt($submitted_by);
        $body_format  = $this->da->quoteSmart($body_format);
        $submitted_on = $this->da->escapeInt($submitted_on);
        $parent_id    = $this->da->escapeInt($parent_id);

        $sql = "INSERT INTO $this->table_name (changeset_id, body, body_format, submitted_by, submitted_on, parent_id)
                VALUES ($changeset_id, $body, $body_format, $submitted_by, $submitted_on, $parent_id)";
        return $this->updateAndGetLastId($sql);
    }
    
    public function delete($changeset_id) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "DELETE
                FROM $this->table_name
                WHERE changeset_id = $changeset_id";
        return $this->update($sql);
    }
}
?>
