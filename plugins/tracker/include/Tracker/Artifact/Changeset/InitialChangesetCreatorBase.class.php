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

declare(strict_types=1);

use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

/**
 * I am a Template Method to create an initial changeset.
 */
abstract class Tracker_Artifact_Changeset_InitialChangesetCreatorBase extends Tracker_Artifact_Changeset_ChangesetCreatorBase //phpcs:ignore
{
    /** @var Tracker_Artifact_ChangesetDao */
    protected $changeset_dao;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        Tracker_Artifact_ChangesetDao $changeset_dao,
        Tracker_ArtifactFactory $artifact_factory,
        EventManager $event_manager,
        Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $fields_validator,
            $fields_retriever,
            $artifact_factory,
            $event_manager,
            $field_initializator
        );

        $this->changeset_dao = $changeset_dao;
        $this->logger        = $logger;
    }

    /**
     * Create the initial changeset of an artifact
     *
     * @param array   $fields_data The artifact fields values
     * @param PFUser  $submitter   The user who did the artifact submission
     * @param int $submitted_on When the changeset is created
     *
     * @return int The Id of the initial changeset, or null if fields were not valid
     */
    public function create(
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        int $submitted_on,
        CreatedFileURLMapping $url_mapping
    ): ?int {
        if (! $this->doesRequestAppearToBeValid($artifact, $fields_data, $submitter)) {
            $this->logger->debug(
                sprintf(
                    'Creation of the first changeset of artifact #%d failed: request does not appear to be valid',
                    $artifact->getId()
                )
            );
            return null;
        }

        $this->initializeAFakeChangesetSoThatListAndWorkflowEncounterAnEmptyState($artifact);

        if (! $this->askWorkflowToUpdateTheRequestAndCheckGlobalRules($artifact, $fields_data, $submitter)) {
            $this->logger->debug(
                sprintf(
                    'Creation of the first changeset of artifact #%d failed: workflow/global rules rejected it',
                    $artifact->getId()
                )
            );
            return null;
        }

        $changeset_id = $this->createChangesetId($artifact, $submitter, $submitted_on);
        if (! $changeset_id) {
            $this->logger->debug(
                sprintf(
                    'Creation of the first changeset of artifact #%d failed: DB failure',
                    $artifact->getId()
                )
            );
            return null;
        }

        $this->storeFieldsValues($artifact, $fields_data, $submitter, $changeset_id, $url_mapping);

        $this->saveArtifactAfterNewChangeset(
            $artifact,
            $fields_data,
            $submitter,
            $artifact->getChangeset($changeset_id)
        );

        $artifact->clearChangesets();

        $this->event_manager->processEvent(new ArtifactCreated($artifact));

        return $changeset_id;
    }

    abstract protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        int $changeset_id,
        CreatedFileURLMapping $url_mapping
    ): void;

    private function storeFieldsValues(
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        int $changeset_id,
        CreatedFileURLMapping $url_mapping
    ): void {
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            $this->saveNewChangesetForField($field, $artifact, $fields_data, $submitter, $changeset_id, $url_mapping);
        }
    }

    /**
     * @return int|false
     */
    private function createChangesetId(
        Tracker_Artifact $artifact,
        PFUser $submitter,
        int $submitted_on
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
    ): bool {
        if ($submitter->isAnonymous() && ! trim($submitter->getEmail())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'email_required'));
            return false;
        }

        if (! $this->fields_validator->validate($artifact, $submitter, $fields_data)) {
            return false;
        }

        return true;
    }

    private function askWorkflowToUpdateTheRequestAndCheckGlobalRules(
        Tracker_Artifact $artifact,
        array &$fields_data,
        PFUser $submitter
    ): bool {
        try {
            $workflow = $artifact->getWorkflow();
            $workflow->validate($fields_data, $artifact, "");
            $workflow->before($fields_data, $submitter, $artifact);
            $augmented_data = $this->field_initializator->process($artifact, $fields_data);
            $workflow->checkGlobalRules($augmented_data);
            return true;
        } catch (Tracker_Workflow_GlobalRulesViolationException $e) {
            $this->logger->debug(
                sprintf('Update of artifact #%d does not respect the global rules', $artifact->getId())
            );
            return false;
        } catch (Tracker_Workflow_Transition_InvalidConditionForTransitionException $e) {
            $this->logger->debug(
                sprintf('Update of artifact #%d does not respect the transition rules', $artifact->getId())
            );
            return false;
        }
    }

    private function initializeAFakeChangesetSoThatListAndWorkflowEncounterAnEmptyState(Tracker_Artifact $artifact): void
    {
        $artifact->setChangesets(array(new Tracker_Artifact_Changeset_Null()));
    }
}
