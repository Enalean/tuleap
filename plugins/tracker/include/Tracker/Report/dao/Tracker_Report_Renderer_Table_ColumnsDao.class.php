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

class Tracker_Report_Renderer_Table_ColumnsDao extends DataAccessObject
{

    public function searchByRendererId($renderer_id)
    {
        $renderer_id  = $this->da->escapeInt($renderer_id);
        $sql = "SELECT col.*
                FROM tracker_report_renderer_table_columns AS col
                    INNER JOIN tracker_field ON (col.field_id = tracker_field.id)
                WHERE renderer_id = $renderer_id
                ORDER BY col.rank";
        return $this->retrieve($sql);
    }

    public function create($renderer_id, $field_id, $width, $rank, $artlink_nature, $artlink_nature_format)
    {
        $renderer_id           = $this->da->escapeInt($renderer_id);
        $field_id              = $this->da->escapeInt($field_id);
        $width                 = $this->da->escapeInt($width);
        $artlink_nature        = is_null($artlink_nature) ? 'NULL' : $this->da->quoteSmart($artlink_nature);
        $artlink_nature_format = is_null($artlink_nature_format) ? 'NULL' : $this->da->quoteSmart($artlink_nature_format);
        if (!isset($rank)) {
            $rank = (int) $this->prepareRanking('tracker_report_renderer_table_columns', 0, $renderer_id, 'end', 'field_id', 'renderer_id');
        } else {
            $rank = $this->da->escapeInt($rank);
        }
        $sql = "INSERT INTO tracker_report_renderer_table_columns (renderer_id, field_id, rank, width, artlink_nature, artlink_nature_format)
                     VALUES ($renderer_id, $field_id, $rank, $width, $artlink_nature, $artlink_nature_format)";

        return $this->update($sql);
    }

    public function remove($renderer_id, $field_id)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);

        $sql = "DELETE FROM tracker_report_renderer_table_columns
                WHERE renderer_id = $renderer_id
                  AND field_id = $field_id";
        return $this->update($sql);
    }

    public function delete($renderer_id)
    {
        $sql = "DELETE FROM tracker_report_renderer_table_columns WHERE renderer_id = " . $this->da->escapeInt($renderer_id);
        return $this->update($sql);
    }

    public function updateRanking($renderer_id, $field_id, $new_rank)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);
        $rank        = (int) $this->prepareRanking('tracker_report_renderer_table_columns', $field_id, $renderer_id, $new_rank, 'field_id', 'renderer_id');

        $sql = "UPDATE tracker_report_renderer_table_columns
                SET rank = $rank
                WHERE renderer_id = $renderer_id
                  AND field_id = $field_id";
        return $this->update($sql);
    }

    public function updateWidth($renderer_id, $field_id, $width)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);
        $width       = $this->da->escapeInt($width);

        $sql = "UPDATE tracker_report_renderer_table_columns
                SET width = $width
                WHERE renderer_id = $renderer_id
                  AND field_id = $field_id";
        return $this->update($sql);
    }

    public function duplicate($from_renderer_id, $to_renderer_id, $field_mapping)
    {
        $from_renderer_id = $this->da->escapeInt($from_renderer_id);
        $to_renderer_id   = $this->da->escapeInt($to_renderer_id);
        $sql = "INSERT INTO tracker_report_renderer_table_columns(renderer_id, field_id, width, rank, artlink_nature, artlink_nature_format)
                SELECT $to_renderer_id, field_id, width, rank, artlink_nature, artlink_nature_format
                FROM tracker_report_renderer_table_columns
                WHERE renderer_id = $from_renderer_id";
        $this->update($sql);

        foreach ($field_mapping as $mapping) {
            $from  = $this->da->escapeInt($mapping['from']);
            $to    = $this->da->escapeInt($mapping['to']);
            $sql = "UPDATE tracker_report_renderer_table_columns
                    SET field_id = $to
                    WHERE renderer_id = $to_renderer_id
                      AND field_id = $from";
            $this->update($sql);
        }
    }
}
