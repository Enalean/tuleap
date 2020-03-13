<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class Cardwall_RendererDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'plugin_cardwall_renderer';
    }

    public function searchByRendererId($renderer_id)
    {
        $renderer_id  = $this->da->escapeInt($renderer_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE renderer_id = $renderer_id ";
        return $this->retrieve($sql);
    }

    public function create($renderer_id, $field_id)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql = "INSERT INTO $this->table_name
                (renderer_id, field_id)
                VALUES ($renderer_id, $field_id)";
        return $this->update($sql);
    }

    public function save($renderer_id, $field_id)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id     = $this->da->escapeInt($field_id);
        $sql = "REPLACE INTO $this->table_name  
                (renderer_id, field_id)
                VALUES ($renderer_id, $field_id)";
        return $this->update($sql);
    }

    public function delete($renderer_id)
    {
        $sql = "DELETE FROM $this->table_name WHERE renderer_id = " . $this->da->escapeInt($renderer_id);
        return $this->update($sql);
    }

    public function duplicate($from_renderer_id, $to_renderer_id)
    {
        $from_renderer_id = $this->da->escapeInt($from_renderer_id);
        $to_renderer_id   = $this->da->escapeInt($to_renderer_id);
        $sql = "INSERT INTO $this->table_name (renderer_id, field_id) 
                SELECT $to_renderer_id, field_id
                FROM $this->table_name
                WHERE renderer_id = $from_renderer_id ";
        return $this->update($sql);
    }
}
