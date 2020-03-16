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


class Tracker_FormElement_Field_Value_FloatDao extends Tracker_FormElement_Field_Value_NumericDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_float';
    }

    public function create($changeset_value_id, $value)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        if ($value === "") {
            $value = "NULL";
        } else {
            $value = floatval($value);
        }
        $sql = "INSERT INTO $this->table_name(changeset_value_id, value)
                VALUES ($changeset_value_id, $value)";
        return $this->update($sql);
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids   = $this->createNoneChangesetValue($tracker_id, $field_id);
        if (!is_array($changeset_value_ids) || empty($changeset_value_ids)) {
            return false;
        }
        $sql = " INSERT INTO $this->table_name(changeset_value_id, value)
                 VALUES
                  ( " . implode(' , NULL ), ( ', $changeset_value_ids) . ", NULL)";
        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, value)
                SELECT $to, value
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }
}
