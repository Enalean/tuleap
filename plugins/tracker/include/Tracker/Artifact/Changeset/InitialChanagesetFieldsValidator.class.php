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
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

/**
 * I validate fields for initial changeset
 */
class Tracker_Artifact_Changeset_InitialChangesetFieldsValidator extends Tracker_Artifact_Changeset_FieldsValidator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    protected function canValidateField(
        Artifact $artifact,
        Tracker_FormElement_Field $field,
        PFUser $user
    ): bool {
        //we do not validate if the field is required and we can't submit the field
        return ! ($field->isRequired() && ! $field->userCanSubmit($user));
    }

    protected function validateField(
        Artifact $artifact,
        Tracker_FormElement_Field $field,
        \PFUser $user,
        $submitted_value
    ) {
        $last_changeset_value = null;
        $is_submission        = true;

        return $field->validateFieldWithPermissionsAndRequiredStatus(
            $artifact,
            $submitted_value,
            $user,
            $last_changeset_value,
            $is_submission
        );
    }

    public static function build(): self
    {
        $form_element_factory    = \Tracker_FormElementFactory::instance();
        $artifact_factory        = Tracker_ArtifactFactory::instance();

        $artifact_link_usage_dao = new \Tuleap\Tracker\Admin\ArtifactLinksUsageDao();
        $artifact_link_validator = new \Tuleap\Tracker\FormElement\ArtifactLinkValidator(
            $artifact_factory,
            new NaturePresenterFactory(
                new \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao(),
                $artifact_link_usage_dao
            ),
            $artifact_link_usage_dao
        );

        return new self($form_element_factory, $artifact_link_validator);
    }
}
