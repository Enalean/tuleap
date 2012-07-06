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

class Cardwall_OnTop_Config_MappingFieldValueCollection implements Countable {

    /**
     * @var array
     */
    private $mapping_values = array();

    public function add(Cardwall_OnTop_Config_MappingFieldValue $mapping_value) {
        $this->mapping_values[$mapping_value->getField()->getId()][$mapping_value->getColumn()][$mapping_value->getValue()] = $mapping_value;
    }

    /**
     * @return array of Cardwall_OnTop_Config_MappingFieldValue
     */
    public function has(Tracker_FormElement_Field $field, $value, $column) {
        return isset($this->mapping_values[$field->getId()][$column][$value]);
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->mapping_values);
    }
}
?>
