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

require_once 'MappingFieldValue.class.php';
require_once 'MappingFieldValueNoField.class.php';
require_once 'MappingFieldValueCollection.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/ColumnMappingFieldValueDao.class.php';

class Cardwall_OnTop_Config_MappingFieldValueCollectionFactory {

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $dao;

    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_factory;

    /**
     * @var Cardwall_OnTop_Config_ColumnFactory
     */
    private $column_factory;

    public function __construct(Cardwall_OnTop_ColumnMappingFieldValueDao $dao, TrackerFactory $tracker_factory, Tracker_FormElementFactory $element_factory, Cardwall_OnTop_Config_ColumnFactory $column_factory) {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
        $this->element_factory = $element_factory;
        $this->column_factory  = $column_factory;
    }

    /**
     * @return Cardwall_OnTop_Config_MappingFieldValueCollection
     */
    public function getCollection(Tracker $tracker) {
        $collection       = new Cardwall_OnTop_Config_MappingFieldValueCollection();
        $columns          = $this->column_factory->getColumns($tracker);
        $mappings         = $this->getMappingFieldValuesIndexedByTracker($tracker);
        $project_trackers = $this->tracker_factory->getTrackersByGroupId($tracker->getGroupId());
        $project_trackers = array_diff($project_trackers, array($tracker));
        foreach ($project_trackers as $project_tracker) {
            if (isset($mappings[$project_tracker->getId()])) {
                $this->instantiateFromCustomMappings($collection, $project_tracker, $mappings[$project_tracker->getId()]);
            } else {
                $this->instantiateFromStatusField($collection, $project_tracker, $columns);
            }
        }
        return $collection;
    }

    private function instantiateFromCustomMappings(Cardwall_OnTop_Config_MappingFieldValueCollection $collection, Tracker $project_tracker, $tracker_mapping) {
        list($field_id, $value_mappings) = each($tracker_mapping);
        if ($field_id) {
            $field = $this->element_factory->getFieldById($field_id);
        } else {
            $field = $project_tracker->getStatusField();
        }
        foreach ($value_mappings as $value_id => $column_id) {
            $collection->add(
                new Cardwall_OnTop_Config_MappingFieldValue(
                    $project_tracker,
                    $field,
                    $value_id,
                    $column_id
                )
            );
        }
    }

    private function instantiateFromStatusField(Cardwall_OnTop_Config_MappingFieldValueCollection $collection, Tracker $project_tracker, $columns) {
        $at_least_one_mapping_found = false;
        $field           = $project_tracker->getStatusField();
        $tracker_columns = $this->getTrackerColumnsIndexedByLabel($project_tracker);
        foreach ($columns as $column) {
            if (isset($tracker_columns[$column->getLabel()])) {
                $at_least_one_mapping_found = true;
                $collection->add(
                    new Cardwall_OnTop_Config_MappingFieldValue(
                        $project_tracker,
                        $field,
                        $tracker_columns[$column->getLabel()]->getId(),
                        $column->getId()
                    )
                );
            }
        }
        if (! $at_least_one_mapping_found) {
            $collection->add(new Cardwall_OnTop_Config_MappingFieldValueNoField($project_tracker));
        }
    }

    private function getMappingFieldValuesIndexedByTracker(Tracker $tracker) {
        $mappings = array();
        foreach ($this->dao->searchMappingFieldValues($tracker->getId()) as $row) {
            $mappings[$row['tracker_id']][$row['field_id']][$row['value_id']] = $row['column_id'];
        }
        return $mappings;
    }

    private function getTrackerColumnsIndexedByLabel(Tracker $tracker) {
        $columns         = array();
        $tracker_columns = $this->column_factory->getColumnsFromStatusField($tracker);
        foreach ($tracker_columns as $col) {
            $columns[$col->getLabel()] = $col;
        }
        return $columns;
    }
}
?>
