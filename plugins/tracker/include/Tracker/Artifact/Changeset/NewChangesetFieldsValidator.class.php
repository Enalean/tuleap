<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * I validate fields for new changeset (update of artifact)
 */
class Tracker_Artifact_Changeset_NewChangesetFieldsValidator extends Tracker_Artifact_Changeset_FieldsValidator {

    protected function canValidateField(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field
    ) {
        $last_changeset = $artifact->getLastChangeset();

        //we do not validate if we are in submission mode, the field is required and we can't submit the field
        return !(!$last_changeset && $field->isRequired() && !$field->userCanSubmit());
    }

    protected function validateField(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field,
        $submitted_value
    ) {
        $is_submission        = false;
        $last_changeset_value = $this->getLastChangesetValue($artifact, $field);

        return $field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value, $last_changeset_value, $is_submission);
    }

    private function getLastChangesetValue(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field
    ) {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        return $last_changeset->getValue($field);
    }
}
