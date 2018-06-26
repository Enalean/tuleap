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

use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Webhook\WebhookRetriever;
use Tuleap\Webhook\Emitter;

/**
 * I am a Template Method to create an initial changeset.
 */
abstract class Tracker_Artifact_Changeset_InitialChangesetCreatorBase extends Tracker_Artifact_Changeset_ChangesetCreatorBase {

    /** @var Tracker_Artifact_ChangesetDao */
    protected $changeset_dao;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        Tracker_FormElementFactory                 $formelement_factory,
        Tracker_Artifact_ChangesetDao              $changeset_dao,
        Tracker_ArtifactFactory                    $artifact_factory,
        EventManager                               $event_manager,
        Emitter                                    $emitter,
        WebhookRetriever                           $webhook_retriever
    ) {
        parent::__construct(
            $fields_validator,
            $formelement_factory,
            $artifact_factory,
            $event_manager,
            $emitter,
            $webhook_retriever
        );

        $this->changeset_dao = $changeset_dao;
    }

    /**
     * Create the initial changeset of an artifact
     *
     * @param array   $fields_data The artifact fields values
     * @param PFUser  $submitter   The user who did the artifact submission
     * @param integer $submitted_on When the changeset is created
     *
     * @return int The Id of the initial changeset, or null if fields were not valid
     */
    public function create(Tracker_Artifact $artifact, array $fields_data, PFUser $submitter, $submitted_on) {
        if (! $this->doesRequestAppearToBeValid($artifact, $fields_data, $submitter)) {
            return;
        }

        $this->initializeAFakeChangesetSoThatListAndWorkflowEncounterAnEmptyState($artifact);

        if (! $this->askWorkflowToUpdateTheRequestAndCheckGlobalRules($artifact, $fields_data, $submitter)) {
            return;
        }

        $changeset_id = $this->createChangesetId($artifact, $submitter, $submitted_on);
        if (! $changeset_id) {
            return;
        }

        $this->storeFieldsValues($artifact, $fields_data, $submitter, $changeset_id);

        $this->saveArtifactAfterNewChangeset(
            $artifact,
            $fields_data,
            $submitter,
            $artifact->getChangeset($changeset_id)
        );

        $artifact->clearChangesets();

        $this->event_manager->processEvent(new ArtifactCreated($artifact));
        $this->emitWebhooks($artifact, $submitter, 'create');

        return $changeset_id;
    }

    /**
     * @return void
     */
    protected abstract function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        $changeset_id
    );

    private function storeFieldsValues(Tracker_Artifact $artifact, array $fields_data, PFUser $submitter, $changeset_id) {
        $used_fields = $this->formelement_factory->getUsedFields($artifact->getTracker());
        foreach ($used_fields as $field) {
            $this->saveNewChangesetForField($field, $artifact, $fields_data, $submitter, $changeset_id);
        }
    }

    private function createChangesetId(
        Tracker_Artifact $artifact,
        PFUser $submitter,
        $submitted_on
    ) {
        $email = null;
        if ($submitter->isAnonymous()) {
            $email = $submitter->getEmail();
        }

        return $this->changeset_dao->create($artifact->getId(), $submitter->getId(), $email, $submitted_on);
    }

    private function doesRequestAppearToBeValid(
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter
    ) {
        if ($submitter->isAnonymous() && ! trim($submitter->getEmail())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'email_required'));
            return false;
        }

        if (! $this->fields_validator->validate($artifact, $fields_data)) {
            return false;
        }

        return true;
    }

    private function askWorkflowToUpdateTheRequestAndCheckGlobalRules(
        Tracker_Artifact $artifact,
        array &$fields_data,
        PFUser $submitter
    ) {
        $workflow = $artifact->getWorkflow();
        $workflow->before($fields_data, $submitter, $artifact);
        $augmented_data = $this->field_initializator->process($artifact, $fields_data);

        try {
            $workflow->checkGlobalRules($augmented_data, $this->formelement_factory);
            return true;
        } catch (Tracker_Workflow_GlobalRulesViolationException $e) {
            return false;
        }
    }

    private function initializeAFakeChangesetSoThatListAndWorkflowEncounterAnEmptyState(Tracker_Artifact $artifact) {
        $artifact->setChangesets(array(new Tracker_Artifact_Changeset_Null()));
    }
}