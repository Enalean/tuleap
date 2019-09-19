<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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


class Cardwall_OnTop_Config_TrackerMappingFactory
{

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $dao;

    /**
     * @var Cardwall_OnTop_Config_ValueMappingFactory
     */
    private $value_mapping_factory;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $element_factory,
        Cardwall_OnTop_ColumnMappingFieldDao $dao,
        Cardwall_OnTop_Config_ValueMappingFactory $value_mapping_factory
    ) {
        $this->tracker_factory       = $tracker_factory;
        $this->element_factory       = $element_factory;
        $this->dao                   = $dao;
        $this->value_mapping_factory = $value_mapping_factory;
    }

    /**
     * @return array of Tracker
     */
    public function getTrackers(Tracker $tracker)
    {
        $trackers = $this->tracker_factory->getTrackersByGroupId($tracker->getGroupId());
        return array_diff($trackers, array($tracker));
    }

    /**
     * Get all mappings defined for the cardwall on top of a tracker
     *
     * @return Cardwall_OnTop_Config_TrackerMapping[]
     */
    public function getMappings(Tracker $tracker, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $mappings = array();
        foreach ($this->dao->searchMappingFields($tracker->getId()) as $row) {
            $this->instantiateMappingFromRow($tracker, $mappings, $row, $columns);
        }
        return $mappings;
    }

    private function instantiateMappingFromRow(Tracker $tracker, array &$mappings, array $row, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $mapping_tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
        if ($mapping_tracker && $mapping_tracker != $tracker) {
            //TODO: field is used?
            $available_fields = $this->element_factory->getUsedSbFields($mapping_tracker);
            $mapping_field    = $this->element_factory->getFieldById($row['field_id']);
            if ($mapping_field) {
                $mapping = $this->instantiateFreestyleMapping($tracker, $mappings, $mapping_tracker, $available_fields, $mapping_field);
            } else {
                $status_field   = $mapping_tracker->getStatusField();
                if ($status_field) {
                    $mapping = $this->instantiateMappingStatus($tracker, $mappings, $mapping_tracker, $available_fields, $columns);
                } else {
                    $mapping = $this->instantiateNoFieldMapping($mapping_tracker, $available_fields);
                }
            }
            $mappings[$mapping_tracker->getId()] = $mapping;
        }
    }

    /**
     * @return Cardwall_OnTop_Config_TrackerMapping
     */
    private function instantiateMappingStatus(Tracker $tracker, array &$mappings, Tracker $mapping_tracker, array $available_fields, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $mapping_values = array();
        $status_field   = $mapping_tracker->getStatusField();
        $mapping_values = $this->value_mapping_factory->getStatusMappings($mapping_tracker, $columns);
        return new Cardwall_OnTop_Config_TrackerMappingStatus(
            $mapping_tracker,
            $available_fields,
            $mapping_values,
            $status_field
        );
    }

    /**
     * @return Cardwall_OnTop_Config_TrackerMappingFreestyle
     */
    private function instantiateFreestyleMapping(Tracker $tracker, array &$mappings, Tracker $mapping_tracker, array $available_fields, $mapping_field)
    {
        $mapping_values = $this->value_mapping_factory->getMappings($tracker, $mapping_tracker, $mapping_field);
        return new Cardwall_OnTop_Config_TrackerMappingFreestyle(
            $mapping_tracker,
            $available_fields,
            $mapping_values,
            $mapping_field
        );
    }

    /**
     * @return Cardwall_OnTop_Config_TrackerMappingFreestyle
     */
    private function instantiateNoFieldMapping(Tracker $mapping_tracker, array $available_fields)
    {
        return new Cardwall_OnTop_Config_TrackerMappingNoField(
            $mapping_tracker,
            $available_fields
        );
    }
}
