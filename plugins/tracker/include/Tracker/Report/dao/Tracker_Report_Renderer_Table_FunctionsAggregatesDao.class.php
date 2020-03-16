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

class Tracker_Report_Renderer_Table_FunctionsAggregatesDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_renderer_table_functions_aggregates';
    }

    public function searchByRendererId($renderer_id)
    {
        $renderer_id  = $this->da->escapeInt($renderer_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE renderer_id = $renderer_id ";
        return $this->retrieve($sql);
    }

    public function create($renderer_id, $field_id, $aggregate)
    {
        $allowed = array('SUM', 'AVG', 'STD', 'COUNT', 'COUNT_GRBY', 'MIN', 'MAX');
        if (in_array($aggregate, $allowed)) {
            $renderer_id = $this->da->escapeInt($renderer_id);
            $field_id    = $this->da->escapeInt($field_id);
            $aggregate   = $this->da->quoteSmart($aggregate);
            $sql = "INSERT INTO $this->table_name(renderer_id, field_id, aggregate)
                                           VALUES($renderer_id, $field_id, $aggregate)";
            return $this->update($sql);
        }
        return false;
    }

    public function remove($renderer_id, $field_id, $aggregate)
    {
        $renderer_id = $this->da->escapeInt($renderer_id);
        $field_id    = $this->da->escapeInt($field_id);
        $aggregate   = $this->da->quoteSmart($aggregate);

        $sql = "DELETE FROM $this->table_name 
                WHERE renderer_id = $renderer_id
                  AND field_id = $field_id
                  AND aggregate = $aggregate";
        return $this->update($sql);
    }

    public function deleteByRendererId($renderer_id)
    {
        $sql = "DELETE FROM $this->table_name WHERE renderer_id = " . $this->da->escapeInt($renderer_id);
        return $this->update($sql);
    }

    public function deleteByFieldId($field_id)
    {
        $sql = "DELETE FROM $this->table_name WHERE field_id = " . $this->da->escapeInt($field_id);
        return $this->update($sql);
    }

    public function duplicate($from_renderer_id, $to_renderer_id, $field_mapping)
    {
        $from_renderer_id = $this->da->escapeInt($from_renderer_id);
        $to_renderer_id   = $this->da->escapeInt($to_renderer_id);
        $sql = "INSERT INTO $this->table_name(renderer_id, field_id, aggregate)
                SELECT $to_renderer_id, field_id, aggregate
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
