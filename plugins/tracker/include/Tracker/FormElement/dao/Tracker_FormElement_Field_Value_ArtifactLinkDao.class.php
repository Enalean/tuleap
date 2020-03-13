<?php
/**
 * Copyright (c) Enalean, 2015 — 2018. All Rights Reserved.
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

class Tracker_FormElement_Field_Value_ArtifactLinkDao extends Tracker_FormElement_Field_ValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_artifactlink';
    }

    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);

        $sql = "SELECT cv.*, a.tracker_id, a.last_changeset_id
                FROM tracker_changeset_value_artifactlink AS cv
                    INNER JOIN tracker_artifact AS a ON (a.id = cv.artifact_id)
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = a.id)
                    INNER JOIN tracker ON (tracker.id = a.tracker_id)
                    INNER JOIN groups ON (groups.group_id = tracker.group_id)
                WHERE changeset_value_id = $changeset_value_id
                    AND groups.status = 'A'
                ORDER BY tracker_artifact_priority_rank.rank";

        return $this->retrieve($sql);
    }

    public function searchReverseLinksById($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT a.id as artifact_id, a.last_changeset_id, t.group_id, t.item_name as keyword, t.id as tracker_id, artlink.nature as nature
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                    JOIN groups ON (groups.group_id = t.group_id)
                WHERE artlink.artifact_id = $artifact_id
                    AND groups.status = 'A'";

        return $this->retrieve($sql);
    }

    public function searchIsChildReverseLinksById($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $is_child    = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD);

        $sql = "SELECT DISTINCT a.id as artifact_id, a.last_changeset_id, t.group_id, t.item_name as keyword, t.id as tracker_id, artlink.nature as nature
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                    JOIN groups ON (groups.group_id = t.group_id)
                WHERE artlink.artifact_id = $artifact_id
                    AND groups.status = 'A'
                    AND nature = $is_child";

        return $this->retrieve($sql);
    }

    public function create($changeset_value_id, $nature, array $artifact_ids, $keyword, $group_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $nature             = $nature ? $this->da->quoteSmart($nature) : 'NULL';
        $keyword            = $this->da->quoteSmart($keyword);
        $group_id           = $this->da->escapeInt($group_id);

        $sql_values = array();
        foreach ($artifact_ids as $id) {
            $id           = $this->da->escapeInt($id);
            $sql_values[] = "($changeset_value_id, $nature, $id, $keyword, $group_id)";
        }

        $sql = "INSERT INTO tracker_changeset_value_artifactlink
                    (changeset_value_id, nature, artifact_id, keyword, group_id)
                VALUES" . implode(',', $sql_values);

        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, nature, artifact_id, keyword, group_id)
                SELECT $to, nature, artifact_id, keyword, group_id
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }

        return true;
    }

    public function updateItemName($group_id, $oldKeyword, $keyword)
    {
        $group_id = $this->da->quoteSmart($group_id);
        $keyword = $this->da->quoteSmart($keyword);
        $oldKeyword = $this->da->quoteSmart($oldKeyword);
        $sql = "UPDATE $this->table_name SET 
			keyword=$keyword
            WHERE keyword=$oldKeyword AND group_id=$group_id";
        return $this->update($sql);
    }

    public function deleteReference($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "DELETE FROM $this->table_name
                WHERE artifact_id = $artifact_id";
        return $this->update($sql);
    }
}
