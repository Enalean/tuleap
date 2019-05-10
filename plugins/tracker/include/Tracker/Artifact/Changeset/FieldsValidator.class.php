<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

/**
 * I validate fields
 */
abstract class Tracker_Artifact_Changeset_FieldsValidator //phpcs:ignore
{
    /** @var Tracker_FormElementFactory */
    protected $formelement_factory;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory
    ) {
        $this->formelement_factory = $formelement_factory;
    }

    /**
     * Validate the fields contained in $fields_data, and update $fields_data for invalid data
     * $fields_data is an array of [field_id] => field_data
     *
     * @param array $fields_data The field data
     *
     * @return bool true if all fields are valid, false otherwise. This function update $field_data (set values to null if not valid)
     */
    public function validate(Tracker_Artifact $artifact, \PFUser $user, $fields_data)
    {
        $is_valid    = true;
        $used_fields = $this->formelement_factory->getUsedFields($artifact->getTracker());
        foreach ($used_fields as $field) {
            $submitted_value = $this->getSubmittedValue($field, $fields_data);
            if ($this->canValidateField($artifact, $field)) {
                $is_valid = $this->validateField($artifact, $field, $user, $submitted_value) && $is_valid;
            }
        }

        return $is_valid;
    }

    /**
     * @return bool
     */
    abstract protected function canValidateField(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field
    );

    /**
     * @return bool
     */
    abstract protected function validateField(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field,
        \PFUser $user,
        $submitted_value
    );

    private function getSubmittedValue(Tracker_FormElement_Field $field, $fields_data)
    {
        $submitted_value = null;
        if ($this->isFieldSubmitted($field, $fields_data)) {
            $submitted_value = $fields_data[$field->getId()];
        }

        return $submitted_value;
    }

    /**
     * @return bool
     */
    private function isFieldSubmitted(Tracker_FormElement_Field $field, array $fields_data)
    {
        return isset($fields_data[$field->getId()]);
    }
}
