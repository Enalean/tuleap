<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_FormElement_Field_MultiSelectbox extends Tracker_FormElement_Field_Selectbox {
    
    public $default_properties = array(
        'size' => array(
            'value' => 7,
            'type'  => 'string',
            'size'  => 3,
        ),
    );
    
    /**
     * @return boolean
     */
    public function isMultiple() {
        return true;
    }
    
    protected function getDao() {
        return new Tracker_FormElement_Field_MultiSelectboxDao();
    }
    
    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties, 
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return boolean true if success
     */
    public function delete() {
        return $this->getDao()->delete($this->id);
    }
    
    protected function getMaxSize() {
        return $this->getproperty('size') ? $this->getproperty('size') : parent::getMaxSize();
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','multiselectbox');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','multiselectbox_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-list-box.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-list-box--plus.png');
    }

    /**
     * Change the type of the multi select box
     * @param string $type the new type
     *
     * @return boolean true if the change is allowed and successful
     */
    public function changeType($type) {
        // only "sb" available at the moment.
        if ($type === 'sb' || $type === 'cb') {
            // We should remove the entry in msb table
            // However we keep it for the case where admin changes its mind.
            return true;
        }
        return false;
    }
    
    /**
     * Augment data from request
     * With multi select boxes, when nothing is selected, 
     * $fields_data does not contains any entry for the field.
     * => augment $fields_data with None value (100)
     *
     * @param array &$fields_data The user submitted value
     *
     * @return void
     */
    public function augmentDataFromRequest(&$fields_data) {

        if(isset($fields_data['request_method_called']) && $fields_data['request_method_called'] = 'artifact-update') {
            return;
            /* When updating an artifact, we do not want this method to reset the selected options.
             *
             * This method is in iteself somewhat of a hack. Its aim is to set default values for multiselect fields
             * that do not have a value in the $fields_data array. However, this method assumes that EVERY field
             * and its value(s) will be submitted. This is a BAD assumption since it is possible to submit only those 
             * fields that have changed. In that case, we do not want to set a default value but, rather, use the 
             * existing one.
             */
        }

        if ((!isset($fields_data[$this->getId()]) || !is_array($fields_data[$this->getId()])) && !$this->isRequired() && $this->userCanUpdate()) {
            $fields_data[$this->getId()] = array('100');
        }
    }

    public function getFieldDataFromCSVValue($csv_value) {
        if ($csv_value == null) {
            return array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }
        return parent::getFieldDataFromCSVValue($csv_value);
    }

    /**
     * @return boolean true if the value corresponds to what we defined as "none"
     */
    public function isNone($value) {
        return $this->isScalarNone($value) || (is_array($value) && $this->isArrayNone($value));
    }

    private function isScalarNone($value) {
        return $value === null || $value === '';
    }

    private function isArrayNone(array $value) {
        return $this->arrayContainsNone($value) || $this->arrayIsEmpty($value);
    }

    private function arrayContainsNone(array $value) {
        return count($value) == 1 && array_pop($value) == '100';
    }

    private function arrayIsEmpty($value) {
        return count($value) == 0;
    }
}
?>
