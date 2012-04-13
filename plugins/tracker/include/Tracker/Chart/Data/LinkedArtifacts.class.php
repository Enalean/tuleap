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

require_once 'LinkedArtifactsDao.class.php';
require_once 'IProvideDataForBurndownChart.class.php';

class Tracker_Chart_Burndown_Data_LinkedArtifacts implements Tracker_Chart_Data_IProvideDataForBurndownChart {
    private $artifact_ids     = array();
    private $remaining_effort = array();
    private $min_day = 99999;
    private $max_day = 0;
    
    public function __construct(array $linked_artifacts, $remaining_effort_field_name) {
        $burndown_dao         = $this->getBurndownDao();
        $form_element_factory = Tracker_FormElementFactory::instance();
        
        $artifact_ids_by_tracker = array();
        foreach($linked_artifacts as $linked_artifact) {
            $tracker_id  = $linked_artifact->getTracker()->getId();
            $artifact_id = $linked_artifact->getId();
            $artifact_ids_by_tracker[$tracker_id][] = $artifact_id;
            $this->artifact_ids[]                   = $artifact_id;
        }

        foreach ($artifact_ids_by_tracker as $tracker_id => $artifact_ids) {
            $effort_field         = $form_element_factory->getFormElementByName($tracker_id, $remaining_effort_field_name);

            if ($effort_field) {
                $effort_field_id   = $effort_field->getId();
                $effort_field_type = $form_element_factory->getType($effort_field);
                $dar = $burndown_dao->searchRemainingEffort($effort_field_id, $effort_field_type, $artifact_ids);
                foreach ($dar as $row) {
                    $day   = $row['day'];
                    $id    = $row['id'];
                    $value = $row['value'];

                    if (!isset($this->remaining_effort[$day])) {
                        $this->remaining_effort[$day] = array();
                    }

                    $this->remaining_effort[$day][$id] = $value;

                    $this->max_day = max($this->max_day, $day);
                    $this->min_day = min($this->min_day, $day);
                }
            }
        }
    }
    
    protected function getBurndownDao() {
        return new Tracker_Chart_Burndown_Data_LinkedArtifactsDao();
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
