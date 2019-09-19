<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Migration_V3_FieldsDao extends DataAccessObject
{

    public function create($tv3_id, $tv5_id)
    {
        $this->duplicateFields($tv3_id, $tv5_id);
        $this->duplicateFieldUsageAndRanking($tv3_id, $tv5_id);
        $this->reorderFieldsForPrepareRankingUsage($tv5_id);
        $this->migrateFieldType($tv3_id, $tv5_id);
        $this->insertIntoFieldList($tv3_id, $tv5_id);
        $this->insertIntoFieldListBindStatic($tv5_id);
        $this->insertIntoFieldListBindStaticValue($tv3_id, $tv5_id);
        $this->insertIntoFieldListBindUsers($tv3_id, $tv5_id);
        $this->insertIntoFieldListBindDecorators($tv3_id, $tv5_id);
        $this->insertBindType($tv5_id);
        $this->insertBindTypeUsers($tv5_id);
    }

    private function duplicateFields($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field (
                    old_id,
                    tracker_id,
                    parent_id,
                    formElement_type,
                    name,
                    label,
                    description,
                    use_it,
                    rank,
                    scope,
                    required)
                SELECT field_id,
                  $tv5_id,
                  field_set_id,
                  '',
                  field_name,
                  REPLACE(REPLACE(label, '&gt;', '>'), '&lt;', '<'),
                  REPLACE(REPLACE(description, '&gt;', '>'), '&lt;', '<'),
                  0,
                  0,
                  scope,
                  IF(empty_ok = 1
                        OR field_name = 'submitted_by'
                        OR field_name = 'open_date'
                        OR field_name = 'last_update_date'
                        OR field_name = 'artifact_id'
                    , 0
                    , 1)
                FROM artifact_field
                WHERE field_name NOT IN('comment_type_id') AND group_artifact_id = $tv3_id";
        $this->update($sql);
    }

    private function duplicateFieldUsageAndRanking($tv3_id, $tv5_id)
    {
        $sql = "UPDATE tracker_field AS f, artifact_field_usage AS u
                    SET f.use_it = u.use_it, f.rank = u.place, f.parent_id = If(u.use_it, f.parent_id, 0)
                    WHERE f.old_id = u.field_id
                      AND f.tracker_id = $tv5_id AND u.group_artifact_id = $tv3_id";
        $this->update($sql);
    }

    private function reorderFieldsForPrepareRankingUsage($tv5_id)
    {
        $this->update("SET @counter = 0");
        $this->update("SET @previous = NULL");
        $sql = "UPDATE tracker_field
                    INNER JOIN (SELECT @counter := IF(@previous = parent_id, @counter + 1, 1) AS new_rank,
                                       @previous := parent_id,
                                       tracker_field.*
                                FROM tracker_field
                                WHERE tracker_id = $tv5_id
                                ORDER BY parent_id, rank, id
                               ) as R1 USING(parent_id,id)
                SET tracker_field.rank = R1.new_rank
                WHERE tracker_field.tracker_id = $tv5_id";
        $this->update($sql);
    }

    private function migrateFieldType($tv3_id, $tv5_id)
    {
        $sql = "UPDATE tracker_field AS f, artifact_field as a
                    SET f.formElement_type = CASE
                            WHEN a.display_type = 'SB' AND f.name = 'submitted_by' THEN 'subby'
                            WHEN a.display_type = 'SB' THEN 'sb'
                            WHEN a.display_type = 'MB' THEN 'msb'
                            WHEN a.display_type = 'TF' AND a.data_type = 1 THEN 'string'
                            WHEN a.display_type = 'TF' AND a.data_type = 2 AND name <> 'artifact_id' THEN 'int'
                            WHEN a.display_type = 'TF' AND a.data_type = 2 AND name = 'artifact_id' THEN 'aid'
                            WHEN a.display_type = 'TF' AND a.data_type = 3 THEN 'float'
                            WHEN a.display_type = 'TA' THEN 'text'
                            WHEN a.display_type = 'DF' AND f.name = 'open_date'        THEN 'subon'
                            WHEN a.display_type = 'DF' AND f.name = 'last_update_date' THEN 'lud'
                            WHEN a.display_type = 'DF' THEN 'date'
                            ELSE a.display_type END,
                        f.notifications = CASE
                            WHEN a.display_type = 'SB' AND f.name = 'submitted_by' THEN 1
                            WHEN f.name = 'assigned_to' OR f.name = 'multi_assigned_to' THEN 1
                            ELSE 0 END
                    WHERE f.old_id = a.field_id
                      AND f.tracker_id = $tv5_id AND a.group_artifact_id = $tv3_id";
         $this->update($sql);
    }

    private function insertIntoFieldList($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list(field_id, bind_type)
                    SELECT f.id, CASE WHEN a.value_function = '' OR a.value_function IS NULL THEN 'static' ELSE 'users' END
                    FROM tracker_field AS f INNER JOIN artifact_field as a
                        ON (a.field_id = f.old_id AND f.tracker_id = $tv5_id AND a.group_artifact_id = $tv3_id)
                    WHERE f.formElement_type IN ('sb', 'msb')";
        $this->update($sql);
    }

    private function insertIntoFieldListBindStatic($tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_static(field_id, is_rank_alpha)
                    SELECT field_id, 0 FROM tracker_field_list AS l INNER JOIN tracker_field AS f
                        ON (l.field_id = f.id AND f.tracker_id = $tv5_id)
                    WHERE bind_type = 'static'";
        $this->update($sql);
    }

    private function insertIntoFieldListBindStaticValue($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_static_value(old_id, field_id, label, description, rank, is_hidden)
                SELECT v.value_id,
                    f.id,
                    REPLACE(REPLACE(v.value, '&gt;', '>'), '&lt;', '<'),
                    REPLACE(REPLACE(v.description, '&gt;', '>'), '&lt;', '<'),
                    v.order_id,
                    IF(v.status = 'H', 1, 0)
                FROM artifact_field_value_list AS v INNER JOIN tracker_field AS f
                     ON (v.field_id = f.old_id AND f.tracker_id = $tv5_id AND v.group_artifact_id = $tv3_id AND v.value_id != 100)";
        $this->update($sql);
    }

    private function insertIntoFieldListBindUsers($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_users(field_id, value_function)
                SELECT l.field_id, a.value_function
                FROM tracker_field_list AS l
                     INNER JOIN tracker_field AS f ON (f.id = l.field_id)
                     INNER JOIN artifact_field as a ON (a.field_id = f.old_id AND f.tracker_id = $tv5_id AND a.group_artifact_id = $tv3_id)
                WHERE bind_type = 'users'";
        $this->update($sql);
    }

    private function insertIntoFieldListBindDecorators($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_decorator(field_id, value_id, red, green, blue)
                SELECT f.id, b.id, 218 as red,
                    CASE b.old_id
                    WHEN 1 THEN 218
                    WHEN 2 THEN 208
                    WHEN 3 THEN 202
                    WHEN 4 THEN 192
                    WHEN 5 THEN 186
                    WHEN 6 THEN 176
                    WHEN 7 THEN 170
                    WHEN 8 THEN 144
                    ELSE 138 END as green,
                    CASE b.old_id
                    WHEN 1 THEN 218
                    WHEN 2 THEN 208
                    WHEN 3 THEN 202
                    WHEN 4 THEN 192
                    WHEN 5 THEN 186
                    WHEN 6 THEN 176
                    WHEN 7 THEN 170
                    WHEN 8 THEN 144
                    ELSE 138 END as blue
                FROM tracker_field_list_bind_static_value AS b
                     INNER JOIN tracker_field AS f  ON (f.id = b.field_id)
                     INNER JOIN artifact_field as a ON (a.field_id = f.old_id AND f.tracker_id = $tv5_id AND a.group_artifact_id = $tv3_id)
                WHERE a.field_name = 'severity' AND b.old_id BETWEEN 1 AND 9";
        $this->update($sql);
    }

    private function insertBindType($tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list(field_id, bind_type)
                    SELECT id, 'users'
                    FROM tracker_field
                    WHERE formElement_type = 'subby' AND tracker_id = $tv5_id";
        $this->update($sql);
    }

    private function insertBindTypeUsers($tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_users(field_id, value_function)
                    SELECT id, 'artifact_submitters'
                    FROM tracker_field
                    WHERE formElement_type = 'subby' AND tracker_id = $tv5_id";
        $this->update($sql);
    }
}
