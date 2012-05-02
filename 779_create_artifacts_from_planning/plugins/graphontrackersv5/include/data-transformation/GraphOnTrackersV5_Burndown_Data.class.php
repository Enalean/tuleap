<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once TRACKER_BASE_DIR.'/Tracker/Chart/Data/IProvideDataForBurndownChart.class.php';
/**
 * this class build data required to build a burndown
 * 
 */
class GraphOnTrackersV5_Burndown_Data implements Tracker_Chart_Data_IProvideDataForBurndownChart {
    private $artifact_ids     = array();
    private $remaining_effort = array();
    private $min_day = PHP_INT_MAX;
    private $max_day = 0;
 
    /**
     * Constructor
     * 
     * @param ressource $query_result result of a db_query
     * @param array     $artifact_ids array of artifact_ids
     */
    public function __construct($query_result, array $artifact_ids) {
        $this->artifact_ids = $artifact_ids;
        
        while ($row = db_fetch_array($query_result)) {
            $current_day = $row['day'];
            $current_id  = $row['id'];
            if (!isset($this->remaining_effort[$current_day])) {
                $this->remaining_effort[$current_day] = array();
            }
            $this->remaining_effort[$current_day][$current_id] = $row['value'];
            $this->max_day = max($this->max_day, $current_day);
            $this->min_day = min($this->min_day, $current_day);
        }
    }
    
    public function getRemainingEffort() {
        return $this->remaining_effort;
    }
    
    public function getMinDay() {
        return $this->min_day;
    }
    
    public function getMaxDay() {
        return $this->max_day;
    }
    
    public function getArtifactIds() {
        return $this->artifact_ids;
    }
}

?>
