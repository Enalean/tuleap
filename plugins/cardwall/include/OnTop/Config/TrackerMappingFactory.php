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

use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatusField;
use Tuleap\Tracker\Tracker;

class Cardwall_OnTop_Config_TrackerMappingFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct(
        private readonly TrackerFactory $tracker_factory,
        private readonly Tracker_FormElementFactory $element_factory,
        private readonly Cardwall_OnTop_ColumnMappingFieldDao $dao,
        private readonly Cardwall_OnTop_Config_ValueMappingFactory $value_mapping_factory,
        private readonly RetrieveSemanticStatusField $status_field_retriever,
    ) {
    }

    /**
     * @return array of Tracker
     */
    public function getTrackers(Tracker $tracker)
    {
        $trackers = $this->tracker_factory->getTrackersByGroupId($tracker->getGroupId());
        return array_diff($trackers, [$tracker]);
    }

    /**
     * Get all mappings defined for the cardwall on top of a tracker
     *
     * @return Cardwall_OnTop_Config_TrackerMapping[]
     */
    public function getMappings(Tracker $tracker, ColumnCollection $columns)
    {
        $mappings = [];
        foreach ($this->dao->searchMappingFields($tracker->getId()) as $row) {
            $this->instantiateMappingFromRow($tracker, $mappings, $row, $columns);
        }
        return $mappings;
    }

    private function instantiateMappingFromRow(Tracker $tracker, array &$mappings, array $row, ColumnCollection $columns)
    {
        $mapping_tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
        if ($mapping_tracker && $mapping_tracker->getId() != $tracker->getId()) {
            //TODO: field is used?
            $available_fields = $this->element_factory->getUsedSbFields($mapping_tracker);
            $mapping_field    = $this->element_factory->getFieldById($row['field_id']);
            if ($mapping_field) {
                $mapping = $this->instantiateFreestyleMapping($tracker, $mappings, $mapping_tracker, $available_fields, $mapping_field);
            } else {
                $status_field = $this->status_field_retriever->fromTracker($mapping_tracker);
                if ($status_field) {
                    $mapping = $this->instantiateMappingStatus($status_field, $mapping_tracker, $available_fields, $columns);
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
    private function instantiateMappingStatus(ListField $status_field, Tracker $mapping_tracker, array $available_fields, ColumnCollection $columns)
    {
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
