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

class Tracker_Migration_V3_FieldsDefaultValuesDao extends DataAccessObject
{

    public function create($tv3_id, $tv5_id)
    {
        $this->insertDefaultValuesForMsb($tv3_id, $tv5_id);
        $this->insertDefaultValuesForDate($tv3_id, $tv5_id);
        $this->insertDefautValuesForInt($tv3_id, $tv5_id);
        $this->insertDefautValuesForFloat($tv3_id, $tv5_id);
        $this->insertDefautValuesForText($tv3_id, $tv5_id);
        $this->insertDefautValuesForString($tv3_id, $tv5_id);
        $this->insertDefautValuesForListBindStaticOnSbFields($tv3_id, $tv5_id);
        $this->insertDefautValuesForListBindUsersOnSbFields($tv3_id, $tv5_id);
        $this->insertDefaultValuesIsNoneForSbFields($tv3_id, $tv5_id);
        $this->insertDefautSingleValueForListBindStaticOnMsbFields($tv3_id, $tv5_id);
        $this->insertDefautSingleValueForListBindUsersOnMsbFields($tv3_id, $tv5_id);
        $this->insertMultipleDefaultValuesForStaticMsbFields($tv3_id, $tv5_id);
        $this->insertMultipleDefaultValuesForUsersMsbFields($tv3_id, $tv5_id);
    }

    private function insertDefaultValuesForMsb($tv3_id, $tv5_id)
    {
        $sql = "REPLACE INTO tracker_field_msb (field_id, size)
                SELECT id, CAST(display_size AS SIGNED INTEGER) AS size
                FROM tracker_field INNER JOIN artifact_field ON(old_id = field_id
                                        AND tracker_id = group_artifact_id
                                        AND tracker_id = $tv5_id AND group_artifact_id = $tv3_id
                                        AND display_size <> ''
                                        AND formElement_type = 'msb')";
        $this->update($sql);
    }

    private function insertDefaultValuesForDate($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_date (field_id, default_value, default_value_type)
                SELECT f.id, old.default_value, IF(old.default_value = '', 0, 1)
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'date'
                    )";
        $this->update($sql);
    }

    private function insertDefautValuesForInt($tv3_id, $tv5_id)
    {
        $sql = "REPLACE INTO tracker_field_int (field_id, default_value, maxchars, size)
                SELECT f.id,
                       IF (old.default_value = '',  NULL, old.default_value),
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER), 0),
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER), 5)
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND
                        old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'int'
                    )";
        $this->update($sql);
    }

    private function insertDefautValuesForFloat($tv3_id, $tv5_id)
    {
        $sql = "REPLACE INTO tracker_field_float (field_id, default_value, maxchars, size)
                SELECT f.id,
                       IF (old.default_value = '', NULL, old.default_value),
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER), 0),
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER), 5)
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'float'
                    )";
        $this->update($sql);
    }

    private function insertDefautValuesForText($tv3_id, $tv5_id)
    {
        $sql = "REPLACE INTO tracker_field_text (field_id, default_value, `rows`, cols)
                SELECT f.id,
                       old.default_value,
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER), 10),
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER), 50)
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id
                        AND f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id
                        AND f.formElement_type = 'text')";
        $this->update($sql);
    }

    private function insertDefautValuesForString($tv3_id, $tv5_id)
    {
        $sql = "REPLACE INTO tracker_field_string (field_id, default_value, maxchars, size)
                SELECT f.id,
                       old.default_value,
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER), 0),
                       IF (display_size LIKE '%/%', CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER), 30)
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'string'
                    )";
        $this->update($sql);
    }

    private function insertDefautValuesForListBindStaticOnSbFields($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                SELECT f.id, new.id
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'sb' AND
                        (old.value_function IS NULL OR old.value_function = ''))
                    INNER JOIN tracker_field_list_bind_static_value AS new ON (
                        old.default_value = new.old_id
                        AND new.field_id = f.id)";
        $this->update($sql);
    }

    private function insertDefautValuesForListBindUsersOnSbFields($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                SELECT f.id, user.user_id
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'sb' AND
                        (old.value_function IS NOT NULL AND old.value_function <> ''))
                    INNER JOIN user ON (
                        old.default_value = user.user_id AND
                        user.user_id <> 100)";
        $this->update($sql);
    }

    private function insertDefaultValuesIsNoneForSbFields($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                SELECT f.id, old.default_value
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'sb' AND
                        old.default_value = 100)";
        $this->update($sql);
    }

    private function insertDefautSingleValueForListBindStaticOnMsbFields($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
               SELECT f.id, new.id
               FROM tracker_field AS f
                   INNER JOIN artifact_field AS old ON (
                       f.old_id = old.field_id AND
                       f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                       f.formElement_type = 'msb' AND
                       (old.value_function IS NULL OR old.value_function = '') AND
                       POSITION(',' IN old.default_value) = 0)
                   INNER JOIN tracker_field_list_bind_static_value AS new ON (
                       old.default_value = new.old_id AND
                       new.field_id = f.id
                       )";
        $this->update($sql);
    }

    private function insertDefautSingleValueForListBindUsersOnMsbFields($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
               SELECT f.id, user.user_id
               FROM tracker_field AS f
                   INNER JOIN artifact_field AS old ON (
                       f.old_id = old.field_id AND
                       f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                       f.formElement_type = 'msb' AND
                       (old.value_function IS NOT NULL AND old.value_function <> '') AND
                       POSITION(',' IN old.default_value) = 0)
                   INNER JOIN user ON (
                       old.default_value = user.user_id AND
                       user.user_id <> 100
                       )";
        $this->update($sql);
    }

    private function insertMultipleDefaultValuesForStaticMsbFields($tv3_id, $tv5_id)
    {
        $sql = "SELECT f.id, old.default_value
                FROM tracker_field AS f
                INNER JOIN artifact_field AS old ON (
                    f.old_id = old.field_id AND
                    f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                    f.formElement_type = 'msb' AND
                    (old.value_function IS NULL OR old.value_function = '') AND
                    POSITION(',' IN old.default_value) <> 0)";
        $res   = $this->retrieve($sql);

        if ($res) {
            while ($row = $res->getRow()) {
                $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                             SELECT " . $row['id'] . ", new.id
                             FROM tracker_field_list_bind_static_value AS new
                             WHERE new.field_id = " . $row['id'] . " AND
                                   new.old_id IN (" . $row['default_value'] . ")";
                $this->update($sql);
            }
        }
    }

    private function insertMultipleDefaultValuesForUsersMsbFields($tv3_id, $tv5_id)
    {
        $sql = "SELECT f.id, old.default_value
                FROM tracker_field AS f
                    INNER JOIN artifact_field AS old ON (
                        f.old_id = old.field_id AND
                        f.tracker_id = $tv5_id AND old.group_artifact_id = $tv3_id AND
                        f.formElement_type = 'msb' AND
                        (old.value_function IS NOT NULL AND old.value_function <> '') AND
                        POSITION(',' IN old.default_value) <> 0)";
        $res   = $this->retrieve($sql);

        if ($res) {
            while ($row = $res->getRow()) {
                $sql = "INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
                     SELECT " . $row['id'] . ", user_id
                     FROM user
                     WHERE user_id IN (" . $row['default_value'] . ") AND
                           user_id <> 100";
                $this->update($sql);
            }
        }
    }
}
