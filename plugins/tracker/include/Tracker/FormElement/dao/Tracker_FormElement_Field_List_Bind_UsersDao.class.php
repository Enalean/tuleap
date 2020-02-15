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

class Tracker_FormElement_Field_List_Bind_UsersDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_users';
    }
    public function searchByFieldId($field_id)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }
    public function duplicate($from_field_id, $to_field_id)
    {
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_field_id    = $this->da->escapeInt($to_field_id);
        $sql = "INSERT INTO $this->table_name (field_id, value_function)
                SELECT $to_field_id, value_function
                FROM $this->table_name
                WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
    public function save($field_id, $value_function)
    {
        $field_id       = $this->da->escapeInt($field_id);
        $value_function = $this->da->quoteSmart(implode(',', $value_function));
        $sql = "REPLACE INTO $this->table_name (field_id, value_function)
                VALUES ($field_id, $value_function)";
        return $this->update($sql);
    }

    public function searchChangesetValues($changeset_id, $field_id, $display_name_query, $display_name_order)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql = "SELECT user.user_id AS id, user.user_name, user.realname, $display_name_query
                FROM user
                     INNER JOIN tracker_changeset_value_list AS l ON (l.bindvalue_id = user.user_id)
                     INNER JOIN tracker_changeset_value AS c
                     ON ( l.changeset_value_id = c.id
                      AND c.changeset_id = $changeset_id
                      AND c.field_id = $field_id
                     )
                ORDER BY $display_name_order";
        return $this->retrieve($sql);
    }
}
