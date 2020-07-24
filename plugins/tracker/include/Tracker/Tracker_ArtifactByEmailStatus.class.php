<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

class Tracker_ArtifactByEmailStatus
{

    /** @var MailGatewayConfig */
    private $tracker_plugin_config;

    public function __construct(MailGatewayConfig $tracker_plugin_config)
    {
        $this->tracker_plugin_config = $tracker_plugin_config;
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
        $description_field = $tracker->getDescriptionField();
        return $title_field !== null && $description_field !== null;
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsPossible(Tracker $tracker)
    {
        if ($this->isSemanticDefined($tracker)) {
            $title_field       = $tracker->getTitleField();
            $description_field = $tracker->getDescriptionField();
            return $this->isRequiredFieldsValid($tracker, $title_field, $description_field);
        }
        return false;
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
        Tracker_FormElement_Field $description_field
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
