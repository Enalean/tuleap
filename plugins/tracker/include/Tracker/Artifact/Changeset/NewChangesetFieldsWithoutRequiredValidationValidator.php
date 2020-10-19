<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tracker_Artifact_Changeset_FieldsValidator;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * I validate fields for both initial and new changesets but without validating required property
 */
class NewChangesetFieldsWithoutRequiredValidationValidator extends Tracker_Artifact_Changeset_FieldsValidator
{
    protected function canValidateField(
        Artifact $artifact,
        Tracker_FormElement_Field $field,
        \PFUser $user
    ): bool {
        return true;
    }

    protected function validateField(
        Artifact $artifact,
        Tracker_FormElement_Field $field,
        \PFUser $user,
        $submitted_value
    ) {
        return $field->validateField($artifact, $submitted_value);
    }
}
