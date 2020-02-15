<?php
/*
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * Originally written by Yoann CELTON, 2013. Jtekt Europe SAS.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class GraphOnTrackersV5_Chart_CumulativeFlowDao extends DataAccessObject
{

    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'plugin_graphontrackersv5_cumulative_flow_chart';
    }

    public function searchById($id)
    {
        $id = $this->da->escapeInt($id);
        $sql = "SELECT * FROM $this->table_name WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function delete($id)
    {
        $id = $this->da->escapeInt($id);
        $sql = "DELETE FROM $this->table_name WHERE id = $id";
        return $this->update($sql);
    }

    public function save($id, $field_id, $start_date, $stop_date, $scale)
    {
        $id         = $this->da->escapeInt($id);
        $field_id   = $this->da->escapeInt($field_id);
        $start_date = $this->da->escapeInt($start_date);
        $stop_date  = $this->da->escapeInt($stop_date);
        $scale      = $this->da->escapeInt($scale);
        $sql = "REPLACE INTO $this->table_name (id, field_id, start_date, stop_date, scale)
                VALUES ($id, $field_id, $start_date, $stop_date, $scale)";
        return $this->update($sql);
    }

    public function duplicate($from_chart_id, $to_chart_id, $field_mapping)
    {
        $from_chart_id = $this->da->escapeInt($from_chart_id);
        $to_chart_id   = $this->da->escapeInt($to_chart_id);
        $sql = "INSERT INTO $this->table_name (id, field_id, start_date, stop_date, scale)
                SELECT $to_chart_id, field_id, start_date, stop_date, scale
                FROM $this->table_name
                WHERE id = $from_chart_id";

        $this->update($sql);
        foreach ($field_mapping as $mapping) {
            $from  = $this->da->escapeInt($mapping['from']);
            $to    = $this->da->escapeInt($mapping['to']);
            $sql = "UPDATE $this->table_name
                    SET field_id = $to
                    WHERE id = $to_chart_id
                      AND field_id = $from";
            $this->update($sql);
        }
    }
}
