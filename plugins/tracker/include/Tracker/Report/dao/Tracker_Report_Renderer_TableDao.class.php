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

class Tracker_Report_Renderer_TableDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_renderer_table';
    }

    public function searchByRendererId($renderer_id)
    {
        $renderer_id  = $this->da->escapeInt($renderer_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE renderer_id = $renderer_id ";
        return $this->retrieve($sql);
    }

    public function create($renderer_id, $chunksz)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $chunksz     = $this->da->escapeInt($chunksz);
        $sql = "INSERT INTO $this->table_name
                (renderer_id, chunksz)
                VALUES ($renderer_id, $chunksz)";
        return $this->update($sql);
    }

    public function save($renderer_id, $chunksz, $multisort)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $chunksz     = $this->da->escapeInt($chunksz);
        $multisort   = $multisort ? 1 : 0;
        $sql = "UPDATE $this->table_name SET 
                   chunksz = $chunksz,
                   multisort = $multisort
                WHERE renderer_id = $renderer_id ";
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
        $sql = "INSERT INTO $this->table_name (renderer_id, chunksz, multisort) 
                SELECT $to_renderer_id, chunksz, multisort
                FROM $this->table_name
                WHERE renderer_id = $from_renderer_id ";
        return $this->update($sql);
    }
}
