<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

class ArtifactXMLExporterDao extends DataAccessObject
{

    public function searchArtifacts($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $summary = $this->unconvertHtmlspecialcharsAlias('artifact.summary', 'summary');
        $details = $this->unconvertHtmlspecialcharsAlias('details', 'details');

        $sql = "SELECT artifact_id, $summary, $details, severity, status_id, open_date, user_name AS submitted_by, close_date
                FROM artifact
                    LEFT JOIN user ON (submitted_by = user_id)
                WHERE group_artifact_id = $tracker_id";

        return $this->retrieve($sql);
    }

    public function searchHistory($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $old_value = $this->unconvertHtmlspecialcharsAlias('h.old_value', 'old_value');

        $comment = $this->unconvertHtmlspecialchars('h.new_value');

        $sql = "SELECT
                    h.artifact_history_id AS id,
                    f.data_type, 
                    f.display_type,
                    f.value_function,
                    h.field_name,
                    $old_value,
                    h.new_value,
                    h.date,
                    h.mod_by,
                    IFNULL(user.user_name, h.email) AS submitted_by,
                    IF(h.email, 1, 0) AS is_anonymous,
                    IF(h.field_name REGEXP '^(comment|lbl_[0-9]+_comment)$', $comment, '') AS comment,
                    h.format
                FROM artifact_history h
                    INNER JOIN artifact a ON (a.artifact_id = h.artifact_id)
                    LEFT JOIN artifact_field f ON (f.field_name = h.field_name AND f.group_artifact_id = a.group_artifact_id)
                    LEFT JOIN user ON (h.mod_by = user.user_id)
                WHERE h.artifact_id = $artifact_id
                ORDER BY id";

        return $this->retrieve($sql);
    }

    /**
     * To be used for some columns:
     *
     * artifact.summary
     * artifact_history.old_value
     * ...
     *
     * @see util_unconvert_htmlspecialchars
     */
    private function unconvertHtmlspecialcharsAlias($column_name, $alias)
    {
        return $this->unconvertHtmlspecialchars($column_name) . " AS $alias";
    }

    private function unconvertHtmlspecialchars($column_name)
    {
        return "REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    $column_name, '&nbsp;', ' '
                                ), '&quot;', '\"'
                            ), '&gt;', '>'
                        ), '&lt;', '<'
                    ), '&amp;', '&'
                )";
    }

    public function searchFilesForArtifact($artifact_id)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);

        $sql = "SELECT *
                FROM artifact_file
                WHERE artifact_id = $artifact_id";
        return $this->retrieve($sql);
    }

    public function searchFile($artifact_id, $filename, $submitted_by, $date)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $filename     = $this->da->quoteSmart($filename);
        $submitted_by = $this->da->escapeInt($submitted_by);
        $date         = $this->da->escapeInt($date);

        $sql = "SELECT id
                FROM artifact_file
                WHERE artifact_id = $artifact_id
                  AND filename = $filename
                  AND submitted_by = $submitted_by
                  AND adddate between $date-3 and $date+3";
        return $this->retrieve($sql);
    }

    public function searchFileBefore($artifact_id, $filename, $date)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $filename     = $this->da->quoteSmart($filename);
        $date         = $this->da->escapeInt($date);

        $sql = "SELECT id
                FROM artifact_file
                WHERE artifact_id = $artifact_id
                  AND filename = $filename
                  AND adddate < $date";
        return $this->retrieve($sql);
    }

    public function searchCCAt($artifact_id, $user_id, $date)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $user_id     = $this->da->escapeInt($user_id);
        $date        = $this->da->escapeInt($date);

        $sql = "SELECT *
                FROM artifact_cc
                WHERE artifact_id = $artifact_id
                  AND added_by = $user_id
                  AND date < $date";
        return $this->retrieve($sql);
    }

    public function searchPermsForArtifact($artifact_id)
    {
        $sql = "SELECT ugroup_id
                FROM permissions
                    INNER JOIN artifact ON (CAST(artifact_id AS CHAR CHARACTER SET utf8) = object_id)
                WHERE permission_type = 'TRACKER_ARTIFACT_ACCESS'
                  AND object_id = '$artifact_id'
                  AND use_artifact_permissions = 1
                ORDER BY ugroup_id";

        return $this->retrieve($sql);
    }

    public function searchFieldValues($artifact_id)
    {
        $sql = "SELECT f.display_type, f.data_type, f.field_name, f.value_function, fv.*
                FROM artifact_field_value fv
                    JOIN artifact         a  ON (a.artifact_id = fv.artifact_id)
                    JOIN artifact_field   f  ON (f.field_id = fv.field_id AND f.group_artifact_id = a.group_artifact_id)
                WHERE fv.artifact_id = $artifact_id";
        return $this->retrieve($sql);
    }

    public function searchFieldValuesList($group_artifact_id, $field_name)
    {
        $sql = "SELECT fvl.value_id, fvl.value
                FROM artifact_field_value_list fvl
                    JOIN artifact_field   f  ON (f.field_id = fvl.field_id AND f.group_artifact_id = fvl.group_artifact_id)
                WHERE  f.group_artifact_id = $group_artifact_id
                   AND f.field_name = '$field_name'";
        return $this->retrieve($sql);
    }

    public function searchUser($user_id)
    {
        if (! is_numeric($user_id)) {
            throw new Exception_TV3XMLInvalidFieldTypeException($user_id);
        }
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT user_name, ldap_id, email
                FROM user
                WHERE user_id = $user_id";
        return $this->retrieve($sql);
    }

    public function getAllUsers()
    {
        $sql = "SELECT DISTINCT(user_id), user_name
                FROM user";
        return $this->retrieve($sql);
    }
}
