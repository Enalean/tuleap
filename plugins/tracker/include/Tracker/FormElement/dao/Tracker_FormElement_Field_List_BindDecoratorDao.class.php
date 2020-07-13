<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

class Tracker_FormElement_Field_List_BindDecoratorDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_decorator';
    }
    public function searchByFieldId($field_id)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM tracker_field_list_bind_decorator
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }

    public function duplicate($from_field_id, $to_field_id, $value_mapping)
    {
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);
        $sql
            = "INSERT INTO tracker_field_list_bind_decorator (field_id, value_id, red, green, blue, tlp_color_name)
                SELECT $to_field_id, value_id, red, green, blue, tlp_color_name
                FROM tracker_field_list_bind_decorator
                WHERE field_id = $from_field_id";
        $this->update($sql);

        foreach ($value_mapping as $from => $to) {
            $from = $this->da->escapeInt($from);
            $to   = $this->da->escapeInt($to);
            $sql
                  = "UPDATE tracker_field_list_bind_decorator
                    SET value_id = $to
                    WHERE field_id = $to_field_id
                      AND value_id = $from";
            $this->update($sql);
        }
    }

    public function saveNoneLegacyColor(int $field_id, int $red, int $green, int $blue): bool
    {
        $field_id = $this->da->escapeInt($field_id);
        $none_id  = $this->da->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE);
        $red      = $this->da->escapeInt($red);
        $green    = $this->da->escapeInt($green);
        $blue     = $this->da->escapeInt($blue);

        $sql = "INSERT INTO tracker_field_list_bind_decorator(field_id, value_id, red, green, blue)
                VALUES ($field_id, $none_id, $red, $green, $blue)";

        return $this->update($sql);
    }

    public function save(int $value_id, int $red, int $green, int $blue): bool
    {
        $value_id = $this->da->escapeInt($value_id);
        $red      = $this->da->escapeInt($red);
        $green    = $this->da->escapeInt($green);
        $blue     = $this->da->escapeInt($blue);

        $sql = "INSERT INTO tracker_field_list_bind_decorator (field_id, value_id, red, green, blue, tlp_color_name)
            SELECT field_id, id, $red, $green, $blue, null
            FROM tracker_field_list_bind_static_value
            WHERE original_value_id = $value_id OR id = $value_id";

        return $this->update($sql);
    }

    public function updateColor(int $value_id, int $red, int $green, int $blue): void
    {
        $value_id = $this->da->escapeInt($value_id);
        $red      = $this->da->escapeInt($red);
        $green    = $this->da->escapeInt($green);
        $blue     = $this->da->escapeInt($blue);

        $sql = "REPLACE INTO tracker_field_list_bind_decorator (field_id, value_id, red, green, blue, tlp_color_name)
            SELECT field_id, id, $red, $green, $blue, null
            FROM tracker_field_list_bind_static_value
            WHERE original_value_id = $value_id OR id = $value_id";
        $this->update($sql);
    }

    public function updateNoneLegacyColor(int $field_id, int $red, int $green, int $blue): void
    {
        $field_id = $this->da->escapeInt($field_id);
        $value_id = $this->da->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE);
        $red      = $this->da->escapeInt($red);
        $green    = $this->da->escapeInt($green);
        $blue     = $this->da->escapeInt($blue);

        $sql = "REPLACE INTO tracker_field_list_bind_decorator (field_id, value_id, red, green, blue, tlp_color_name)
                VALUES ($field_id, $value_id, $red, $green, $blue, null)";
        $this->update($sql);
    }

    public function saveTlpColor(int $value_id, string $tlp_color): bool
    {
        $tlp_color = $this->da->quoteSmart($tlp_color);
        $value_id  = $this->da->escapeInt($value_id);

        $sql = "INSERT INTO tracker_field_list_bind_decorator(field_id, value_id, red, green, blue, tlp_color_name)
            SELECT field_id, id, null, null, null, $tlp_color
            FROM tracker_field_list_bind_static_value
            WHERE original_value_id = $value_id OR id = $value_id";

        return $this->update($sql);
    }

    public function saveNoneTlpColor(int $field_id, string $tlp_color): bool
    {
        $tlp_color = $this->da->quoteSmart($tlp_color);
        $field_id  = $this->da->escapeInt($field_id);
        $value_id = $this->da->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE);

        $sql = "INSERT INTO tracker_field_list_bind_decorator(field_id, value_id, red, green, blue, tlp_color_name)
            VALUES ($field_id, $value_id, null, null, null, $tlp_color)";

        return $this->update($sql);
    }

    public function updateTlpColor(int $value_id, string $tlp_color): void
    {
        $tlp_color = $this->da->quoteSmart($tlp_color);

        $sql = "REPLACE INTO tracker_field_list_bind_decorator (field_id, value_id, red, green, blue, tlp_color_name)
            SELECT field_id, id, null, null, null, $tlp_color
            FROM tracker_field_list_bind_static_value
            WHERE original_value_id = $value_id OR id = $value_id";

        $this->update($sql);
    }

    public function updateNoneTlpColor(int $field_id, string $tlp_color): void
    {
        $tlp_color = $this->da->quoteSmart($tlp_color);
        $value_id = $this->da->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE);

        $sql = "REPLACE INTO tracker_field_list_bind_decorator  (field_id, value_id, red, green, blue, tlp_color_name)
                VALUES ($field_id, $value_id, null, null, null, $tlp_color)";

        $this->update($sql);
    }

    public function delete(int $field_id, int $value_id): void
    {
        $field_id = $this->da->escapeInt($field_id);
        $value_id = $this->da->escapeInt($value_id);
        $sql      = "DELETE FROM tracker_field_list_bind_decorator
                WHERE field_id = $field_id
                  AND value_id = $value_id;";
        $this->update($sql);
    }
}
