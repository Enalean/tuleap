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

class Tracker_Artifact_Changeset_CommentDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_comment';
    }

    public function searchLastVersion($changeset_id)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "SELECT * FROM $this->table_name
                WHERE changeset_id = $changeset_id
                ORDER BY id DESC
                LIMIT 1";
        return $this->retrieve($sql);
    }

    public function searchLastVersionForArtifact($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "SELECT comment_v1.*
                FROM tracker_changeset AS changeset
                  LEFT JOIN tracker_changeset_comment AS comment_v1 ON (comment_v1.changeset_id = changeset.id)
                  LEFT JOIN tracker_changeset_comment AS comment_v2 ON (comment_v2.changeset_id = changeset.id AND comment_v1.id < comment_v2.id)
                WHERE changeset.artifact_id = $artifact_id
                AND comment_v2.id IS NULL
                AND comment_v1.id IS NOT NULL";
        $result = [];
        foreach ($this->retrieve($sql) as $row) {
            $result[$row['changeset_id']] = $row;
        }
        return $result;
    }

    public function createNewVersion($changeset_id, $body, $submitted_by, $submitted_on, $parent_id, $body_format)
    {
        $stripped_body = $this->extractStrippedBody($body, $body_format);

        $changeset_id          = $this->da->escapeInt($changeset_id);
        $body                  = $this->da->quoteSmart($body);
        $submitted_by          = $this->da->escapeInt($submitted_by);
        $body_format           = $this->da->quoteSmart($body_format);
        $submitted_on          = $this->da->escapeInt($submitted_on);
        $parent_id             = $this->da->escapeInt($parent_id);
        $escaped_stripped_body = $this->da->quoteSmart($stripped_body);

        $sql = "INSERT INTO $this->table_name (changeset_id, body, body_format, submitted_by, submitted_on, parent_id)
                VALUES ($changeset_id, $body, $body_format, $submitted_by, $submitted_on, $parent_id)";
        $id  = $this->updateAndGetLastId($sql);

        if ($stripped_body !== "") {
            $sql = "INSERT INTO tracker_changeset_comment_fulltext (comment_id, stripped_body)
                VALUES ($id, $escaped_stripped_body)";
            $this->update($sql);
        }

        return $id;
    }

    public function delete($changeset_id)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $sql = "DELETE
                FROM $this->table_name
                WHERE changeset_id = $changeset_id";
        return $this->update($sql);
    }

    /**
     * @param $body
     * @param $body_format
     *
     * @return string
     */
    protected function extractStrippedBody($body, $body_format)
    {
        if ($body_format === Tracker_Artifact_Changeset_Comment::HTML_COMMENT) {
            return Codendi_HTMLPurifier::instance()->purify($body, CODENDI_PURIFIER_STRIP_HTML);
        }

        return $body;
    }
}
