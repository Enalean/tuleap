<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_ArtifactByEmailStatus
{
    public function __construct(
        private MailGatewayConfig $tracker_plugin_config,
        private readonly RetrieveSemanticDescriptionField $retrieve_description_field,
    ) {
    }

    /**
     * @return bool
     */
    private function isCreationEnabled(Tracker $tracker)
    {
        return $this->tracker_plugin_config->isInsecureEmailgatewayEnabled() && $tracker->isEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    public function canCreateArtifact(Tracker $tracker)
    {
        return $this->isCreationEnabled($tracker)
            && $this->isSemanticDefined($tracker)
            && $this->isRequiredFieldsPossible($tracker);
    }

    /**
     * @return bool
     */
    public function canUpdateArtifactInTokenMode(Tracker $tracker)
    {
        return $this->tracker_plugin_config->isTokenBasedEmailgatewayEnabled() ||
            $this->canUpdateArtifactInInsecureMode($tracker);
    }

    /**
     * @return bool
     */
    public function canUpdateArtifactInInsecureMode(Tracker $tracker)
    {
        return $this->tracker_plugin_config->isInsecureEmailgatewayEnabled() && $tracker->isEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    private function isSemanticDefined(Tracker $tracker)
    {
        $title_field       = $tracker->getTitleField();
        $description_field = $this->retrieve_description_field->fromTracker($tracker);
        return $title_field !== null && $description_field !== null;
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsPossible(Tracker $tracker)
    {
        $title_field       = $tracker->getTitleField();
        $description_field = $this->retrieve_description_field->fromTracker($tracker);
        if (! $description_field || ! $title_field) {
            return false;
        }

        return $this->isRequiredFieldsValid($tracker, $title_field, $description_field);
    }

    /**
     * @return bool
     */
    public function isSemanticConfigured(Tracker $tracker)
    {
        return ! $this->isCreationEnabled($tracker) || $this->isSemanticDefined($tracker);
    }

    /**
     * @return bool
     */
    public function isRequiredFieldsConfigured(Tracker $tracker)
    {
        return ! $this->isCreationEnabled($tracker) || ! $this->isSemanticDefined($tracker) || $this->isRequiredFieldsPossible($tracker);
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsValid(
        Tracker $tracker,
        Tracker_FormElement_Field $title_field,
        Tracker_FormElement_Field $description_field,
    ) {
        $is_required_fields_valid = true;

        $form_elements = $tracker->getFormElementFields();
        foreach ($form_elements as $form_element) {
            if (! $is_required_fields_valid) {
                break;
            }
            if ($form_element->isRequired()) {
                $is_required_fields_valid = $form_element->getId() === $title_field->getId() ||
                    $form_element->getId() === $description_field->getId();
            }
        }

        return $is_required_fields_valid;
    }
}
