<?php
/**
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


    /**
     *  Data Access Object for GraphicReportCharts
     */
class GraphOnTrackersV5_ChartDao extends DataAccessObject
{
    /**
     * Constructs the GraphOnTrackersV5_ChartDao
     */
    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'plugin_graphontrackersv5_chart';
    }

    public function searchByReportId($report_id)
    {
        $sql = "SELECT * FROM plugin_graphontrackersv5_chart WHERE report_graphic_id = ";
        $sql .= $this->da->escapeInt($report_id);
        $sql .= " ORDER BY rank";
        return $this->retrieve($sql);
    }

    public function searchById($id)
    {
        $sql = "SELECT * FROM plugin_graphontrackersv5_chart WHERE id = ";
        $sql .= $this->da->escapeInt($id);
        return $this->retrieve($sql);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM plugin_graphontrackersv5_chart WHERE id = ";
        $sql .= $this->da->escapeInt($id);
        return $this->update($sql);
    }

    public function create($renderer_id, $chart_type, $rank, $title, $description, $width, $height)
    {
        $sql = sprintf(
            "INSERT INTO plugin_graphontrackersv5_chart(report_graphic_id, rank, chart_type, title, description, width, height) VALUES (%d, %d, %s, %s, %s, %d, %d)",
            (int) $renderer_id,
            (int) $rank,
            $this->da->quoteSmart($chart_type),
            $this->da->quoteSmart($title),
            $this->da->quoteSmart($description),
            (int) $width,
            (int) $height
        );
        return $this->updateAndGetLastId($sql);
    }

    public function updatebyId($renderer_id, $id, $rank, $title, $description, $width, $height)
    {
        $sql = sprintf(
            "UPDATE plugin_graphontrackersv5_chart SET rank = %d, title = %s, description = %s, width = %d, height = %d WHERE id = %d",
            (int) $rank,
            $this->da->quoteSmart($title),
            $this->da->quoteSmart($description),
            (int) $width,
            (int) $height,
            (int) $id
        );
        return $this->update($sql);
    }

    public function getSiblings($id)
    {
        $sql = "SELECT c2.* 
                    FROM plugin_graphontrackersv5_chart AS c1 INNER JOIN plugin_graphontrackersv5_chart AS c2 USING(report_graphic_id) 
                    WHERE c1.id = ";
        $sql .= $this->da->escapeInt($id);
        $sql .= " ORDER BY rank";
        return $this->retrieve($sql);
    }

    public function getRank($id)
    {
        $rank = null;
        $sql = "SELECT rank FROM plugin_graphontrackersv5_chart WHERE id = ";
        $sql .= $this->da->escapeInt($id);
        if ($dar = $this->retrieve($sql)) {
            if ($row = $dar->getRow()) {
                $rank = $row['rank'];
            }
        }
        return $rank;
    }

    public function duplicate($from_chart_id, $to_renderer_id)
    {
        $from_chart_id = $this->da->escapeInt($from_chart_id);
        $to_renderer_id   = $this->da->escapeInt($to_renderer_id);
        $sql = "INSERT INTO $this->table_name(report_graphic_id, rank, chart_type, title, description, width, height)
                    SELECT $to_renderer_id, rank, chart_type, title, description, width, height
                    FROM $this->table_name
                    WHERE id = $from_chart_id";
        return $this->updateAndGetLastId($sql);
    }
}
