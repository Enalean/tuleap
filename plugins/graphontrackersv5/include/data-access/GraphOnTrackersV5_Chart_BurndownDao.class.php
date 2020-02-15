<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

class GraphOnTrackersV5_Chart_BurndownDao extends DataAccessObject
{

    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'plugin_graphontrackersv5_scrum_burndown';
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

    public function save($id, $field_id, $start_date, $duration)
    {
        $id         = $this->da->escapeInt($id);
        $field_id   = $this->da->escapeInt($field_id);
        $start_date = $this->da->escapeInt($start_date);
        $duration   = $this->da->escapeInt($duration);
        $sql = "REPLACE INTO $this->table_name (id, field_id, start_date, duration)
                VALUES ($id, $field_id, $start_date, $duration)";
        return $this->update($sql);
    }

    public function duplicate($from_chart_id, $to_chart_id, $field_mapping)
    {
        $from_chart_id = $this->da->escapeInt($from_chart_id);
        $to_chart_id   = $this->da->escapeInt($to_chart_id);
        $sql = "INSERT INTO $this->table_name (id, field_id, start_date, duration)
                SELECT $to_chart_id, field_id, start_date, duration
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
