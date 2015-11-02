<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_ArtifactByEmailStatus {
    private $tracker;
    private $tracker_plugin_config;

    public function __construct(Tracker $tracker, TrackerPluginConfig $tracker_plugin_config) {
        $this->tracker               = $tracker;
        $this->tracker_plugin_config = $tracker_plugin_config;
    }

    /**
     * @return bool
     */
    private function isCreationEnabled() {
        return $this->tracker_plugin_config->isInsecureEmailgatewayEnabled() && $this->tracker->isEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    public function canCreateArtifact() {
        return $this->isCreationEnabled()
            && $this->isSemanticDefined()
            && $this->isRequiredFieldsPossible();
    }

    /**
     * @return bool
     */
    public function canUpdateArtifact() {
        return $this->tracker_plugin_config->isTokenBasedEmailgatewayEnabled() ||
            $this->tracker_plugin_config->isInsecureEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    private function isSemanticDefined() {
        $title_field       = $this->tracker->getTitleField();
        $description_field = $this->tracker->getDescriptionField();
        return $title_field !== null && $description_field !== null;
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsPossible() {
        if ($this->isSemanticDefined()) {
            $title_field       = $this->tracker->getTitleField();
            $description_field = $this->tracker->getDescriptionField();
            return $this->isRequiredFieldsValid($title_field, $description_field);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isSemanticConfigured() {
        return !$this->isCreationEnabled() || $this->isSemanticDefined();
    }

    /**
     * @return bool
     */
    public function isRequiredFieldsConfigured() {
        return !$this->isCreationEnabled() || !$this->isSemanticDefined() || $this->isRequiredFieldsPossible();
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsValid(
        Tracker_FormElement_Field $title_field,
        Tracker_FormElement_Field $description_field
    ) {
        $is_required_fields_valid = true;

        $form_elements = $this->tracker->getFormElementFields();
        reset($form_elements);
        while ($is_required_fields_valid && list(, $form_element) = each($form_elements)) {
            if ($form_element->isRequired()) {
                $is_required_fields_valid = $form_element->getId() === $title_field->getId() ||
                    $form_element->getId() === $description_field->getId();
            }
        }

        return $is_required_fields_valid;
    }
}
