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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;

/**
 * I validate fields
 */
abstract class Tracker_Artifact_Changeset_FieldsValidator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var Tracker_FormElementFactory */
    protected $formelement_factory;
    /**
     * @var ArtifactLinkValidator
     */
    private $artifact_link_validator;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        ArtifactLinkValidator $artifact_link_validator
    ) {
        $this->formelement_factory     = $formelement_factory;
        $this->artifact_link_validator = $artifact_link_validator;
    }

    /**
     * Validate the fields contained in $fields_data, and update $fields_data for invalid data
     * $fields_data is an array of [field_id] => field_data
     *
     * @param array $fields_data The field data
     *
     * @return bool true if all fields are valid, false otherwise. This function update $field_data (set values to null if not valid)
     */
    public function validate(
        Artifact $artifact,
        \PFUser $user,
        $fields_data,
        ChangesetValidationContext $changeset_validation_context
    ): bool {
        $is_valid    = true;
        $used_fields = $this->formelement_factory->getUsedFields($artifact->getTracker());
        foreach ($used_fields as $field) {
            if ($this->canValidateField($artifact, $field, $user)) {
                $submitted_value = $this->getSubmittedValue($field, $fields_data);
                $is_one_field_valid = $this->validateOneField(
                    $artifact,
                    $user,
                    $field,
                    $submitted_value,
                    $changeset_validation_context
                );
                $is_valid           = $is_valid && $is_one_field_valid;
            }
        }

        return $is_valid;
    }

    /**
     * @param mixed $submitted_value
     */
    private function validateOneField(
        Artifact $artifact,
        PFUser $user,
        Tracker_FormElement_Field $field,
        $submitted_value,
        ChangesetValidationContext $changeset_validation_context
    ): bool {
        if ($field instanceof \Tracker_FormElement_Field_ArtifactLink) {
            $is_field_generally_valid = $this->validateField($artifact, $field, $user, $submitted_value);
            if (! $is_field_generally_valid) {
                return false;
            }
            return $this->artifact_link_validator->isValid(
                $submitted_value,
                $artifact,
                $field,
                $changeset_validation_context->getArtifactLinkContext()
            );
        }
        return $this->validateField($artifact, $field, $user, $submitted_value);
    }

    abstract protected function canValidateField(
        Artifact $artifact,
        Tracker_FormElement_Field $field,
        PFUser $user
    ): bool;

    /**
     * @return bool
     */
    abstract protected function validateField(
        Artifact $artifact,
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
