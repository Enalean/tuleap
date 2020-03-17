<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_FormElement_Field_MultiSelectbox extends Tracker_FormElement_Field_Selectbox
{

    public $default_properties = array(
        'size' => array(
            'value' => 7,
            'type'  => 'string',
            'size'  => 3,
        ),
    );

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return true;
    }

    protected function getDao()
    {
        return new Tracker_FormElement_Field_MultiSelectboxDao();
    }

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return bool true if success
     */
    public function delete()
    {
        return $this->getDao()->delete($this->id);
    }

    protected function getMaxSize()
    {
        return $this->getproperty('size') ? $this->getproperty('size') : parent::getMaxSize();
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'multiselectbox');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'multiselectbox_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-list-box.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-list-box--plus.png');
    }

    /**
     * Change the type of the multi select box
     * @param string $type the new type
     *
     * @return bool true if the change is allowed and successful
     */
    public function changeType($type)
    {
        if (in_array($type, array('sb', 'rb', 'cb'))) {
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
    public function augmentDataFromRequest(&$fields_data)
    {
        if (! $this->canAugmentData($fields_data)) {
            return;
        }

        if (isset($fields_data['request_method_called']) &&
            $fields_data['request_method_called'] === 'submit-artifact' &&
            ! $this->userCanSubmit()
        ) {
            return;
        }

        if ((!isset($fields_data[$this->getId()]) || !is_array($fields_data[$this->getId()])) && !$this->isRequired() && $this->userCanUpdate()) {
            $fields_data[$this->getId()] = array('100');
        }
    }

    private function canAugmentData($fields_data)
    {
        /* When updating or massupdate an artifact, we do not want this method to reset the selected options.
         *
         * This method is in iteself somewhat of a hack. Its aim is to set default values for multiselect fields
         * that do not have a value in the $fields_data array. However, this method assumes that EVERY field
         * and its value(s) will be submitted. This is a BAD assumption since it is possible to submit only those
         * fields that have changed. In that case, we do not want to set a default value but, rather, use the
         * existing one.
         */
        if (isset($fields_data['request_method_called']) &&
            ($fields_data['request_method_called'] === 'artifact-update' ||
                $fields_data['request_method_called'] === 'artifact-masschange')
        ) {
            return false;
        }

        return true;
    }

    public function getFieldDataFromCSVValue($csv_value, ?Tracker_Artifact $artifact = null)
    {
        if ($csv_value == null) {
            return array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }

        return parent::getFieldDataFromCSVValue($csv_value, $artifact);
    }

    public function getFieldDataFromRESTValue(array $value, ?Tracker_Artifact $artifact = null)
    {
        if (array_key_exists('bind_value_ids', $value) && is_array($value['bind_value_ids'])) {
            $submitted_bind_value_ids = array_filter(array_unique($value['bind_value_ids']));
            if (empty($submitted_bind_value_ids)) {
                return [Tracker_FormElement_Field_List::NONE_VALUE];
            }

            return array_unique(
                array_map(
                    array($this, 'getBindValueIdFromSubmittedBindValueId'),
                    $value['bind_value_ids']
                )
            );
        }

        throw new Tracker_FormElement_InvalidFieldValueException('List fields values must be passed as an array of ids (integer) in \'bind_value_ids\''
           . ' Example: {"field_id": 1548, "bind_value_ids": [457]}');
    }

    /**
     * @return bool true if the value corresponds to what we defined as "none"
     */
    public function isNone($value)
    {
        return $this->isScalarNone($value) || (is_array($value) && $this->isArrayNone($value));
    }

    private function isScalarNone($value)
    {
        return $value === null || $value === '';
    }

    private function isArrayNone(array $value)
    {
        return $this->arrayContainsNone($value) || $this->arrayIsEmpty($value);
    }

    private function arrayIsEmpty($value)
    {
        return count($value) == 0;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitMultiSelectbox($this);
    }

    public function getDefaultValue()
    {
        $default_array = $this->getBind()->getDefaultValues();
        if (! $default_array) {
            return array(Tracker_FormElement_Field_List_Bind::NONE_VALUE);
        }
        return array_keys($default_array);
    }
}
