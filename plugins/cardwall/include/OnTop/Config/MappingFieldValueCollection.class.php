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

class Cardwall_OnTop_Config_MappingFieldValueCollection {

    /**
     * @var array
     */
    private $mapping_values = array();

    /**
     * @var array of Cardwall_OnTop_Config_MappingFieldValue
     */
    private $mapping_values_by_tracker = array();

    public function add(Cardwall_OnTop_Config_MappingFieldValue $mapping_value) {
        $this->mapping_values
            [$mapping_value->getTracker()->getId()]
            [$mapping_value->getFieldId()]
            [$mapping_value->getColumn()]
            [$mapping_value->getValue()] = $mapping_value;

        $this->mapping_values_by_tracker
            [$mapping_value->getTracker()->getId()][] = $mapping_value;
    }

    /**
     * @return array of Cardwall_OnTop_Config_MappingFieldValue
     */
    public function has(Tracker $tracker, Tracker_FormElement_Field $field, $value, $column) {
        return isset($this->mapping_values[$tracker->getId()][$field->getId()][$column][$value]);
    }

    /**
     * @return array of Cardwall_OnTop_Config_MappingFieldValue or null
     */
    public function getForTracker(Tracker $tracker) {
        if (isset($this->mapping_values_by_tracker[$tracker->getId()])) {
            return $this->mapping_values_by_tracker[$tracker->getId()];
        }
    }
}
?>
