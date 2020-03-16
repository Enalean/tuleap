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

class Tracker_FormElement_Field_Value_OpenListDao extends Tracker_FormElement_Field_Value_ListDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_openlist';
    }

    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE changeset_value_id = $changeset_value_id 
                ORDER BY insertion_order ASC";
        return $this->retrieve($sql);
    }

    public function create($changeset_value_id, $value_ids)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $values = array();
        foreach ($value_ids as $v) {
            $b = $v['bindvalue_id'] ? (int) $v['bindvalue_id'] : 'NULL';
            $o = $v['openvalue_id'] ? (int) $v['openvalue_id'] : 'NULL';
            $values[] = "($changeset_value_id, $b, $o)";
        }
        if ($values) {
            $values = implode(',', $values);
            $sql = "INSERT INTO $this->table_name(changeset_value_id, bindvalue_id, openvalue_id)
                    VALUES $values";
            return $this->update($sql);
        }
        return false;
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $this->createNoneChangesetValue($tracker_id, $field_id);
        //nothing more we do not want values for openlist field
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, bindvalue_id, openvalue_id)
                SELECT $to, bindvalue_id, openvalue_id
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }
}
