<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codex Team.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
    
    require_once('common/dao/include/DataAccessObject.class.php');
    
    /**
     *  Data Access Object for GraphOnTrackers_Report 
     */
    class GraphOnTrackers_ReportDao extends DataAccessObject {
        /**
         * Constructs the GraphOnTrackers_ReportDao
         * @param $da instance of the DataAccess class
         */
        function GraphOnTrackers_ReportDao($da) {
            DataAccessObject::DataAccessObject($da);
        }
        
        public function searchByTrackerIdAndScope($tracker_id, $scope) {
            $sql = "SELECT * FROM plugin_graphontrackers_report_graphic WHERE group_artifact_id = ";
            $sql .= $this->da->escapeInt($tracker_id);
            $sql .= " AND scope = ";
            $sql .= $this->da->quoteSmart($scope);
            return $this->retrieve($sql);
        }
    }
?>
