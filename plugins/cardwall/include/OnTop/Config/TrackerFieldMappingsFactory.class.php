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

class Cardwall_OnTop_Config_TrackerFieldMappingsFactory {
    
    /** @var TrackerFactory */
    private $tracker_factory;
    
    /** @var Cardwall_OnTop_ColumnMappingFieldDao */
    private $dao;
    
    /** @var Cardwall_OnTop_Config_TrackerFieldMappingFactory */
    private $field_mapping_factory;
    
    public function __construct(TrackerFactory $tracker_factory, 
                                Cardwall_OnTop_ColumnMappingFieldDao $dao,
                                Cardwall_OnTop_Config_TrackerFieldMappingFactory $field_mappping_factory) {
        $this->tracker_factory       = $tracker_factory;
        $this->dao                   = $dao;
        $this->field_mapping_factory = $field_mappping_factory;
    }
    
    public function getMappings(Tracker $cardwall_tracker) {
        $trackers = $this->tracker_factory->getTrackersByGroupId($cardwall_tracker->getGroupId());
        $raw_mappings = $this->dao->searchMappingFields($cardwall_tracker->getId());
        $mappings = array();
        foreach ($raw_mappings as $raw_mapping) {
            $tracker    = $trackers[$raw_mapping['tracker_id']];
            $field_id   = $raw_mapping['field_id'];
            $mappings[] = $this->field_mapping_factory->newMapping($tracker, $field_id);
        }
        
        return $mappings; 
    }

    public function getNonMappedTrackers(Tracker $current_tracker) {
        $project_trackers = $this->tracker_factory->getTrackersByGroupId($current_tracker->getGroupId());
        $raw_mappings = $this->dao->searchMappingFields($current_tracker->getId());
        
        $mapped_tracker_ids = array();
        foreach ($raw_mappings as $raw_mapping) {
            $mapped_tracker_ids[] = $raw_mapping['tracker_id'];
        }
        
        $retained_trackers = array();
        foreach ($project_trackers as $id => $tracker) {
            if ($id != $current_tracker->getId() && !in_array($id, $mapped_tracker_ids)) {
                $retained_trackers[$id] = $tracker;
                
            }
        }
        return $retained_trackers;
    }
}
?>
