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

require_once('common/dao/include/DataAccessObject.class.php');

class Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao extends DataAccessObject {

    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_ugroups_value';
    }

    public function searchById($id) {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function searchByFieldId($field_id) {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id
                ORDER BY id";
        return $this->retrieve($sql);
    }
    public function duplicate($from_value_id, $to_field_id) {
        $from_value_id  = $this->da->escapeInt($from_value_id);
        $to_field_id    = $this->da->escapeInt($to_field_id);
        $insert = "INSERT INTO $this->table_name (field_id, ugroup_id)
                SELECT $to_field_id, ugroup_id
                FROM $this->table_name
                WHERE id = $from_value_id";

        return $this->updateAndGetLastId($sql);
    }

    public function create($field_id, $ugroup_id) {
        $field_id  = $this->da->escapeInt($field_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "INSERT INTO $this->table_name (field_id, ugroup_id)
                VALUES ($field_id, $ugroup_id)";
        return $this->updateAndGetLastId($sql);
    }

    public function save($id, $field_id, $ugroup_id) {
        $id        = $this->da->escapeInt($id);
        $field_id  = $this->da->escapeInt($field_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "UPDATE $this->table_name
                SET label = $label,
                    description = $description,
                    rank = $rank,
                    is_hidden = $is_hidden
                WHERE id = $id
                  OR original_value_id = $id";
        return $this->update($sql);
    }

    public function deleteByFieldId($field_id) {
        $id       = $this->da->escapeInt($field_id);
        $sql = "DELETE FROM $this->table_name
                WHERE field_id = $field_id ";

        return $this->update($sql);
    }

    public function searchChangesetValues($changeset_id, $field_id) {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql = "SELECT f.id, f.ugroup_id
                FROM tracker_field_list_bind_ugroups_value AS f
                     INNER JOIN tracker_changeset_value_list AS l ON (l.bindvalue_id = f.id)
                     INNER JOIN tracker_changeset_value AS c
                     ON ( l.changeset_value_id = c.id
                      AND c.changeset_id = $changeset_id
                      AND c.field_id = $field_id
                     )
                ORDER BY f.id";
        return $this->retrieve($sql);
    }
}
?>
