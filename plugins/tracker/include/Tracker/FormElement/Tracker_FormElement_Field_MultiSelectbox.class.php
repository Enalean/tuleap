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

require_once('Tracker_FormElement_Field_Selectbox.class.php');
require_once('dao/Tracker_FormElement_Field_MultiSelectboxDao.class.php');
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
        if ($type === 'sb') {
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
        if ((!isset($fields_data[$this->getId()]) || !is_array($fields_data[$this->getId()])) && !$this->isRequired() && $this->userCanUpdate()) {
            $fields_data[$this->getId()] = array('100');
        }
    }
    
    /**
     * @return boolean true if the value corresponds to what we defined as "none"
     */
    public function isNone($value) {
        return $value === null || $value === '' || (is_array($value) && count($value) ==1 && $value[0] == '100');
    }
}
?>
