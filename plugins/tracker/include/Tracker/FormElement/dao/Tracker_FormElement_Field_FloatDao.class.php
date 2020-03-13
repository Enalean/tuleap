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

/**
 *  Data Access Object for Tracker_FormElement_Field
 */
class Tracker_FormElement_Field_FloatDao extends Tracker_FormElement_SpecificPropertiesDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_float';
    }

    public function save($field_id, $row)
    {
        $field_id  = $this->da->escapeInt($field_id);

        if (isset($row['maxchars']) && (int) $row['maxchars']) {
            $maxchars = $this->da->escapeInt($row['maxchars']);
        } else {
            $maxchars = 0;
        }

        if (isset($row['size']) && (int) $row['size']) {
            $size = $this->da->escapeInt($row['size']);
        } else {
            $size = 30;
        }

        if (isset($row['default_value']) && trim($row['default_value']) !== '') {
            $default_value = (float) $row['default_value'];
        } else {
            $default_value = "NULL";
        }

        $sql = "REPLACE INTO $this->table_name (field_id, maxchars, size, default_value)
                VALUES ($field_id, $maxchars, $size, $default_value)";
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

        $sql = "REPLACE INTO $this->table_name (field_id, maxchars, size, default_value)
                SELECT $to_field_id, maxchars, size, default_value FROM $this->table_name WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
}
