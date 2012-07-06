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

class Cardwall_OnTop_Config_MappimgFieldValue {

    /**
     * @var Tracker
     */
    private $current_tracker;

    /**
     * @var TrackerTracker_FormElement_Field
     */
    private $field;

    /**
     * @var int
     */
    private $value;

    /**
     * @var int
     */
    private $column;

    public function __construct(Tracker $current_tracker, Tracker_FormElement_Field $field, $value, $column) {
        $this->current_tracker = $current_tracker;
        $this->field           = $field;
        $this->value           = $value;
        $this->column          = $column;
    }

    public function getValue() {
        return $this->value;
    }

    public function getColumn() {
        return $this->column;
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getField() {
        return $this->field;
    }
}

class Cardwall_OnTop_Config_MappimgFieldValueCollection implements Countable {

    /**
     * @var array
     */
    private $mapping_values = array();

    public function add(Cardwall_OnTop_Config_MappimgFieldValue $mapping_value) {
        $this->mapping_values[$mapping_value->getField()->getId()][$mapping_value->getColumn()][$mapping_value->getValue()] = $mapping_value;
    }

    /**
     * @return array of Cardwall_OnTop_Config_MappimgFieldValue
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

require_once dirname(__FILE__) .'/../ColumnMappingFieldValueDao.class.php';
class Cardwall_OnTop_Config_MappimgFieldValueCollectionFactory {

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $dao;

    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    public function __construct(Cardwall_OnTop_ColumnMappingFieldValueDao $dao, Tracker_FormElementFactory $element_factory) {
        $this->dao             = $dao;
        $this->element_factory = $element_factory;
    }

    /**
     * @return Cardwall_OnTop_Config_MappimgFieldValueCollection
     */
    public function getCollection(Tracker $tracker) {
        $collection = new Cardwall_OnTop_Config_MappimgFieldValueCollection();
        foreach ($this->dao->searchMappingFieldValues($tracker->getId()) as $row) {
            //TODO: if $row['field_id'] is null, it means that we target the semantic status
            $field = $this->element_factory->getFieldById($row['field_id']);
            if ($field) {
                $collection->add(
                    new Cardwall_OnTop_Config_MappimgFieldValue(
                        $tracker,
                        $field,
                        $row['value_id'],
                        $row['column_id']
                    )
                );
            }
        }
        return $collection;
    }
}
?>
