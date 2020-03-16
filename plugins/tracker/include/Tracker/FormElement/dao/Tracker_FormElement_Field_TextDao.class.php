<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
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

class Tracker_FormElement_Field_TextDao extends Tracker_FormElement_SpecificPropertiesDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_text';
    }

    public function save($field_id, $row)
    {
        $field_id  = $this->da->escapeInt($field_id);

        if (isset($row['rows']) && (int) $row['rows']) {
            $rows = $this->da->escapeInt($row['rows']);
        } else {
            $rows = 10;
        }

        if (isset($row['cols']) && (int) $row['cols']) {
            $cols = $this->da->escapeInt($row['cols']);
        } else {
            $cols = 50;
        }

        if (isset($row['default_value'])) {
            $default_value = $this->da->quoteSmart($row['default_value']);
        } else {
            $default_value = "''";
        }

        $sql = "REPLACE INTO tracker_field_text (field_id, `rows`, cols, default_value)
                VALUES ($field_id, $rows, $cols, $default_value)";
        return $this->retrieve($sql);
    }

    /**
     * Duplicate specific properties of field
     *
     * @param int $from_field_id the field id source
     * @param int $to_field_id   the field id target
     *
     * @return bool true if ok, false otherwise
     */
    public function duplicate($from_field_id, $to_field_id)
    {
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_field_id  = $this->da->escapeInt($to_field_id);

        $sql = "REPLACE INTO tracker_field_text (field_id, `rows`, cols, default_value)
                SELECT $to_field_id, `rows`, cols, default_value FROM tracker_field_text WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
