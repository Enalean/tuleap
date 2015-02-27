<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_REST_Artifact_ArtifactValidator {

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory) {
        $this->formelement_factory = $formelement_factory;
    }

    public function getFieldsDataOnCreate(array $values, Tracker $tracker) {
        $new_values     = array();
        $indexed_fields = $this->getIndexedFields($tracker);
        foreach ($values as $value) {
            $array_representation = $value->toArray();

            $field = $this->getField($indexed_fields, $array_representation);
            $new_values[$field->getId()] = $field->getFieldDataFromRESTValue($array_representation);
        }
        return $new_values;
    }

    public function getFieldsDataOnUpdate(array $values, Tracker_Artifact $artifact) {
        $new_values     = array();
        $indexed_fields = $this->getIndexedFields($artifact->getTracker());
        foreach ($values as $value) {
            $field = $this->getField($indexed_fields, $value);
            $new_values[$field->getId()] = $field->getFieldDataFromRESTValue($value, $artifact);
        }
        return $new_values;
    }

    public function getUsedFieldsWithDefaultValue(Tracker $tracker, array $fields_data, PFUser $user) {
        $fields_data = $this->formelement_factory->getUsedFieldsWithDefaultValue($tracker, $fields_data, $user);
        return $fields_data;
    }

    private function getField(array $indexed_fields, array $value) {
        if (! isset($value['field_id']) || (isset($value['field_id']) && ! is_int($value['field_id']))) {
            throw new Tracker_FormElement_InvalidFieldException('No \'field_id\' or invalid id in submitted value. Field IDs must be integers');
        }
        if (! isset($indexed_fields[$value['field_id']])) {
            throw new Tracker_FormElement_InvalidFieldException('Unknown field '.$value['field_id']);
        }
        return $indexed_fields[$value['field_id']];
    }

    private function getIndexedFields(Tracker $tracker) {
        $indexed_fields = array();
        foreach ($this->formelement_factory->getUsedFields($tracker) as $field) {
            $indexed_fields[$field->getId()] = $field;
        }
        return $indexed_fields;
    }
}
