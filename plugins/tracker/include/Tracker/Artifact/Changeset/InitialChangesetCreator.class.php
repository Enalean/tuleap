<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Webhook\Emitter;

/**
 * I create an initial changeset
 */
class Tracker_Artifact_Changeset_InitialChangesetCreator extends Tracker_Artifact_Changeset_InitialChangesetCreatorBase {

    public function __construct(
        Tracker_Artifact_Changeset_InitialChangesetFieldsValidator $fields_validator,
        Tracker_FormElementFactory                                 $formelement_factory,
        Tracker_Artifact_ChangesetDao                              $changeset_dao,
        Tracker_ArtifactFactory                                    $artifact_factory,
        EventManager                                               $event_manager,
        Emitter                                                    $emitter,
        WebhookFactory                                             $webhook_factory
    ) {
        parent::__construct(
            $fields_validator,
            $formelement_factory,
            $changeset_dao,
            $artifact_factory,
            $event_manager,
            $emitter,
            $webhook_factory
        );
    }

    /**
     * @see parent::saveNewChangesetForField()
     */
    protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        $changeset_id
    ) {
        $is_submission = true;
        $bypass_perms  = true;
        $workflow      = $artifact->getWorkflow();

        if ($this->isFieldSubmitted($field, $fields_data)) {
            if ($field->userCanSubmit()) {
                $field->saveNewChangeset($artifact, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission);
                return;
            } else if ($workflow && $workflow->bypassPermissions($field)) {
                $field->saveNewChangeset($artifact, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission, $bypass_perms);
                return;
            }
        }

        if (!$field->userCanSubmit() && $field->isSubmitable()) {
            $this->pushDefaultValueInSubmittedValues($field, $fields_data);
            $field->saveNewChangeset($artifact, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission, $bypass_perms);
        }
    }

    private function pushDefaultValueInSubmittedValues(Tracker_FormElement_Field $field, array &$fields_data) {
        $fields_data[$field->getId()] = $field->getDefaultValue();
    }
}
