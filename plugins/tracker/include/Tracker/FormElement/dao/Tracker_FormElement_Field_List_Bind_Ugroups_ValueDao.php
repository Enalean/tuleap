<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_ugroups_value';
    }

    public function searchById($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function searchByFieldId($field_id)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id
                ORDER BY id";
        return $this->retrieve($sql);
    }
    public function duplicate($from_value_id, $to_field_id)
    {
        $from_value_id  = $this->da->escapeInt($from_value_id);
        $to_field_id    = $this->da->escapeInt($to_field_id);
        $sql = "REPLACE INTO $this->table_name (field_id, ugroup_id, is_hidden)
                SELECT $to_field_id, u1.ugroup_id, v.is_hidden
                FROM ugroup u1
                    INNER JOIN tracker t ON (
                        t.group_id = u1.group_id AND u1.ugroup_id > 100
                        OR
                        u1.ugroup_id <= 100
                    )
                    INNER JOIN tracker_field AS f ON (t.id = f.tracker_id)
                    INNER JOIN ugroup u2 ON (u1.name = u2.name)
                    INNER JOIN $this->table_name v ON (v.ugroup_id = u2.ugroup_id)
                    WHERE f.id = $to_field_id
                      AND v.id = $from_value_id";

        return $this->updateAndGetLastId($sql);
    }

    public function create($field_id, $ugroup_id, $is_hidden)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $is_hidden = $is_hidden ? 1 : 0;

        $sql = "REPLACE INTO $this->table_name (field_id, ugroup_id, is_hidden)
                VALUES ($field_id, $ugroup_id, $is_hidden)";
        return $this->updateAndGetLastId($sql);
    }

    public function hide($id)
    {
        return $this->toggleHidden($id, 1);
    }

    public function show($id)
    {
        return $this->toggleHidden($id, 0);
    }

    private function toggleHidden($id, $is_hidden)
    {
        $id        = $this->da->escapeInt($id);
        $is_hidden = $is_hidden ? 1 : 0;

        $sql = "UPDATE $this->table_name
                SET is_hidden = $is_hidden
                WHERE id = $id";
        return $this->update($sql);
    }

    public function searchChangesetValues($changeset_id, $field_id)
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql = "SELECT f.id, f.ugroup_id, f.is_hidden
                FROM tracker_field_list_bind_ugroups_value AS f
                     INNER JOIN tracker_changeset_value_list AS l ON (l.bindvalue_id = f.id AND f.field_id = $field_id)
                     INNER JOIN tracker_changeset_value AS c
                     ON ( l.changeset_value_id = c.id
                      AND c.changeset_id = $changeset_id
                      AND c.field_id = $field_id
                     )
                ORDER BY f.id";
        return $this->retrieve($sql);
    }
}
