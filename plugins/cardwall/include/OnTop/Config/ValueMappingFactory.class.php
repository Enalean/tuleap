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

require_once dirname(__FILE__) . '/../../constants.php';

class Cardwall_OnTop_Config_ValueMappingFactory
{

    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $dao;

    public function __construct(
        Tracker_FormElementFactory $element_factory,
        Cardwall_OnTop_ColumnMappingFieldValueDao $dao
    ) {
        $this->element_factory = $element_factory;
        $this->dao             = $dao;
    }

    /**
     * @return array of Cardwall_OnTop_Config_ValueMapping
     */
    public function getStatusMappings(Tracker $mapping_tracker, Cardwall_OnTop_Config_ColumnCollection $columns)
    {
        $mappings = array();
        $status_values = $this->getStatusValuesIndexedByLabel($mapping_tracker);
        foreach ($columns as $master_column) {
            if (isset($status_values[$master_column->getLabel()])) {
                $col = $status_values[$master_column->getLabel()];
                $mappings[$col->getId()] = new Cardwall_OnTop_Config_ValueMapping(
                    $col,
                    $master_column->getId()
                );
            }
        }
        return $mappings;
    }

    /**
     * @return array of Cardwall_OnTop_Config_ValueMapping
     */
    public function getMappings(Tracker $tracker, Tracker $mapping_tracker, Tracker_FormElement_Field $mapping_field)
    {
        // Why does we return a collection indexed on value_id in the case of freestyle mappings, and a collection
        // indexed on column_id in the case of status mappings @see getStatusMappings?????????
        // Shouldn't we let TrackerMapping do the indexing so that code in TrackerMapping might exploit that?
        $mappings = $this->getMappingFieldValuesIndexedByTracker($tracker);
        if (isset($mappings[$mapping_tracker->getId()][$mapping_field->getId()])) {
            return $mappings[$mapping_tracker->getId()][$mapping_field->getId()];
        }
        return array();
    }

    private function getMappingFieldValuesIndexedByTracker(Tracker $tracker)
    {
        $mappings = array();
        foreach ($this->dao->searchMappingFieldValues($tracker->getId()) as $row) {
            $field = $this->element_factory->getFieldById($row['field_id']);
            if ($field) {
                $value = $field->getListValueById($row['value_id']);
                if ($value) {
                    $mappings[$row['tracker_id']][$row['field_id']][$row['value_id']] = new Cardwall_OnTop_Config_ValueMapping(
                        $value,
                        $row['column_id']
                    );
                }
            }
        }
        return $mappings;
    }

    private function getStatusValuesIndexedByLabel(Tracker $mapping_tracker)
    {
        $values = array();
        $field  = $mapping_tracker->getStatusField();
        if ($field) {
            foreach ($field->getVisibleValuesPlusNoneIfAny() as $value) {
                $values[$value->getLabel()] = $value;
            }
        }
        return $values;
    }
}
