<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

class Tracker_FormElement_Field_List_OpenValueDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_openlist_value';
    }
    public function searchById($field_id, $id)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $id        = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id 
                  AND id = $id ";
        return $this->retrieve($sql);
    }

    public function searchByFieldId($field_id)
    {
        $field_id  = $this->da->escapeInt($field_id);

        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id";
        return $this->retrieve($sql);
    }

    public function save($id, $field_id, $label)
    {
        $id       = $this->da->escapeInt($id);
        $field_id = $this->da->escapeInt($field_id);
        $label    = $this->da->quoteSmart($label);
        $sql = "UPDATE $this->table_name 
                SET label = $label
                WHERE field_id = $field_id
                  AND id = $id";
        return $this->update($sql);
    }

    public function create($field_id, $label)
    {
        $field_id = $this->da->escapeInt($field_id);
        $label    = $this->da->quoteSmart($label);
        $sql = "INSERT INTO $this->table_name (field_id, label)
                VALUES ($field_id, $label)";
        return $this->updateAndGetLastId($sql);
    }

    public function searchByKeyword($field_id, $keyword, $limit = 10)
    {
        $field_id = $this->da->escapeInt($field_id);
        $limit = $this->da->escapeInt($limit);
        $keyword = $this->da->quoteLikeValueSurround($keyword);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id
                  AND label LIKE $keyword
                LIMIT $limit";
        return $this->retrieve($sql);
    }

    public function searchByExactLabel($field_id, $label)
    {
        $field_id = $this->da->escapeInt($field_id);
        $label    = $this->da->quoteSmart($label);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE field_id = $field_id
                  AND label = $label";
        return $this->retrieve($sql);
    }
}
