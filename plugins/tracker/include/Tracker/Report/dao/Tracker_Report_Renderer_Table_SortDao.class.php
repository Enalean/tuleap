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

class Tracker_Report_Renderer_Table_SortDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_renderer_table_sort';
    }

    public function searchByRendererId($renderer_id)
    {
        $renderer_id  = $this->da->escapeInt($renderer_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE renderer_id = $renderer_id
                ORDER BY rank";
        return $this->retrieve($sql);
    }

    public function toggleByRendererIdAndFieldId($renderer_id, $field_id)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql = "UPDATE $this->table_name
                SET is_desc = 1 - is_desc
                WHERE renderer_id = $renderer_id AND field_id = $field_id";
        return $this->update($sql);
    }
    public function create($renderer_id, $field_id, $is_desc = 0, $rank = 0)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);
        if (!isset($rank)) {
            $rank        = (int) $this->prepareRanking('tracker_report_renderer_table_sort', 0, $renderer_id, 'end', 'field_id', 'renderer_id');
        } else {
            $rank = $this->da->escapeInt($rank);
        }

        if (!isset($is_desc)) {
            $is_desc = 0;
        } else {
            $is_desc = $this->da->escapeInt($is_desc);
        }
        $sql = "INSERT INTO $this->table_name(renderer_id, field_id, is_desc, rank)
                                       VALUES($renderer_id, $field_id, $is_desc, $rank)";
        return $this->update($sql);
    }

    public function remove($renderer_id, $field_id)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);

        $sql = "DELETE FROM $this->table_name
                WHERE renderer_id = $renderer_id
                  AND field_id = $field_id";
        return $this->update($sql);
    }

    public function delete($renderer_id)
    {
        $sql = "DELETE FROM $this->table_name WHERE renderer_id = " . $this->da->escapeInt($renderer_id);
        return $this->update($sql);
    }

    public function shrink($renderer_id, $field_id_to_keep)
    {
        $renderer_id      = $this->da->escapeInt($renderer_id);
        $field_id_to_keep = $this->da->escapeInt($field_id_to_keep);
        $sql = "DELETE FROM $this->table_name
                WHERE renderer_id = $renderer_id
                  AND field_id <> $field_id_to_keep";
        return $this->update($sql);
    }

    public function duplicate($from_renderer_id, $to_renderer_id, $field_mapping)
    {
        $from_renderer_id = $this->da->escapeInt($from_renderer_id);
        $to_renderer_id   = $this->da->escapeInt($to_renderer_id);
        $sql = "INSERT INTO $this->table_name(renderer_id, field_id, is_desc, rank)
                SELECT $to_renderer_id, field_id, is_desc, rank
                FROM $this->table_name
                WHERE renderer_id = $from_renderer_id";
        $this->update($sql);

        foreach ($field_mapping as $mapping) {
            $from  = $this->da->escapeInt($mapping['from']);
            $to    = $this->da->escapeInt($mapping['to']);
            $sql = "UPDATE $this->table_name
                    SET field_id = $to
                    WHERE renderer_id = $to_renderer_id
                      AND field_id = $from";
            $this->update($sql);
        }
    }
}
