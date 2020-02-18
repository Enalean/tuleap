<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

class Tracker_FormElement_Field_List_Bind_StaticDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_list_bind_static';
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
        $sql = "REPLACE INTO $this->table_name (field_id, is_rank_alpha)
                SELECT $to_field_id, is_rank_alpha
                FROM $this->table_name
                WHERE field_id = $from_field_id";
        return $this->update($sql);
    }
    public function save($field_id, $is_rank_alpha)
    {
        $field_id      = $this->da->escapeInt($field_id);
        $is_rank_alpha = $this->da->escapeInt($is_rank_alpha);
        $sql = "REPLACE INTO $this->table_name (field_id, is_rank_alpha)
                VALUES ($field_id, $is_rank_alpha)";
        return $this->update($sql);
    }

    public function updateChildrenAlphaRank(int $parent_id, int $is_rank_alpha)
    {
        $parent_id     = $this->da->escapeInt($parent_id);
        $is_rank_alpha = $this->da->escapeInt($is_rank_alpha);

        $sql = "UPDATE tracker_field_list_bind_static AS children_static_field
                INNER JOIN tracker_field AS children_field ON children_field.id = children_static_field.field_id
                SET children_static_field.is_rank_alpha = $is_rank_alpha
                WHERE children_field.original_field_id = $parent_id";

        return $this->update($sql);
    }

    public function isRankAlpha($field_id)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $sql = "SELECT is_rank_alpha
                FROM $this->table_name
                WHERE field_id = $field_id ";
        return $this->retrieve($sql);
    }
}
