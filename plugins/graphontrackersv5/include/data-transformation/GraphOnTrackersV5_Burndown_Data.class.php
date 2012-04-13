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

class GraphOnTrackersV5_Burndown_Data implements Tracker_Chart_Data_IProvideDataForBurndownChart {
    private $artifact_ids     = array();
    private $remaining_effort = array();
    private $min_day = 0;
    private $max_day = 0;
 
    public function __construct($res, $artifact_ids) {
        $this->artifact_ids = $artifact_ids;
        
        while ($d = db_fetch_array($res)) {
            if (!isset($this->remaining_effort[$d['day']])) {
                $this->remaining_effort[$d['day']] = array();
            }
            $this->remaining_effort[$d['day']][$d['id']] = $d['value'];
            if ($d['day'] > $this->max_day) $this->max_day=$d['day'];
            if ($d['day'] < $this->min_day) $this->min_day=$d['day'];
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
