<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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

    
    require_once('common/dao/include/DataAccessObject.class.php');
    
    /**
     *  Data Access Object for GraphicReportCharts 
     */
    class GraphOnTrackers_ChartDao extends DataAccessObject {
        /**
         * Constructs the GraphOnTrackers_ChartDao
         */
        function __construct($da) {
            parent::__construct($da);
            $this->table_name = 'plugin_graphontrackers_chart';
        }
        
        public function searchByReportId($report_id) {
            $sql = "SELECT * FROM plugin_graphontrackers_chart WHERE report_graphic_id = ";
            $sql .= $this->da->escapeInt($report_id);
            $sql .= " ORDER BY rank";
            return $this->retrieve($sql);
        }
        
        public function searchById($id) {
            $sql = "SELECT * FROM plugin_graphontrackers_chart WHERE id = ";
            $sql .= $this->da->escapeInt($id);
            return $this->retrieve($sql);
        }
        
        public function delete($id) {
            $sql = "DELETE FROM plugin_graphontrackers_chart WHERE id = ";
            $sql .= $this->da->escapeInt($id);
            return $this->update($sql);
        }
        
        public function create($report_id, $chart_type, $title, $description, $width, $height) {
            $rank = $this->prepareRanking(0, $report_id, 'beginning', 'id', 'report_graphic_id');
            $sql = sprintf("INSERT INTO plugin_graphontrackers_chart(report_graphic_id, rank, chart_type, title, description, width, height) VALUES (%d, %d, %s, %s, %s, %d, %d)",
                (int)$report_id,
                (int)$rank,
                $this->da->quoteSmart($chart_type),
                $this->da->quoteSmart($title),
                $this->da->quoteSmart($description),
                (int)$width,
                (int)$height);
            $inserted = $this->update($sql);
            if ($inserted) {
                $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
                if ($row = $dar->getRow()) {
                    $inserted = $row['id'];
                } else {
                    $inserted = $dar->isError();
                }
            }
            return $inserted;
        }
        
        public function updatebyId($id, $report_id, $rank, $title, $description, $width, $height) {
            $rank = $this->prepareRanking($id, $report_id, $rank, 'id', 'report_graphic_id');
            $sql = sprintf("UPDATE plugin_graphontrackers_chart SET rank = %d, title = %s, description = %s, width = %d, height = %d WHERE id = %d",
                (int)$rank,
                $this->da->quoteSmart($title),
                $this->da->quoteSmart($description),
                (int)$width,
                (int)$height,
                (int)$id
            );
            return $this->update($sql);
        }
        
        public function getSiblings($id) {
            $sql = "SELECT c2.* 
                    FROM plugin_graphontrackers_chart AS c1 INNER JOIN plugin_graphontrackers_chart AS c2 USING(report_graphic_id) 
                    WHERE c1.id = ";
            $sql .= $this->da->escapeInt($id);
            $sql .= " ORDER BY rank";
            return $this->retrieve($sql);
        }
        
        public function getRank($id) {
            $rank = null;
            $sql = "SELECT rank FROM plugin_graphontrackers_chart WHERE id = ";
            $sql .= $this->da->escapeInt($id);
            if ($dar = $this->retrieve($sql)) {
                if ($row = $dar->getRow()) {
                    $rank = $row['rank'];
                }
            }
            return $rank;
        }
    }
?>
