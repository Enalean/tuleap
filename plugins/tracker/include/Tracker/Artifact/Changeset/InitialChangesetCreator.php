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

namespace Tuleap\Tracker\Artifact\Changeset;

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_FieldsValidator;
use Tracker_Artifact_Changeset_Null;
use Tracker_Artifact_Exception_CannotCreateNewChangeset;
use Tracker_Workflow_GlobalRulesViolationException;
use Tracker_Workflow_Transition_InvalidConditionForTransitionException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\SaveInitialChangesetValue;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Workflow\RetrieveWorkflow;

/**
 * I create initial changesets.
 */
final class InitialChangesetCreator implements CreateInitialChangeset
{
    public function __construct(
        private Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        private FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        private Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator,
        private LoggerInterface $logger,
        private ArtifactChangesetSaver $artifact_changeset_saver,
        private AfterNewChangesetHandler $after_new_changeset_handler,
        private RetrieveWorkflow $workflow_retriever,
        private SaveInitialChangesetValue $save_initial_changeset_value,
    ) {
    }

    public function create(
        Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        int $submitted_on,
        CreatedFileURLMapping $url_mapping,
        TrackerImportConfig $import_config,
        ChangesetValidationContext $changeset_validation_context,
    ): ?int {
        $are_fields_valid = $this->doesRequestAppearToBeValid(
            $artifact,
            $fields_data,
            $submitter,
            $changeset_validation_context
        );
        if (! $are_fields_valid) {
            $this->logger->debug(
                sprintf(
                    'Creation of the first changeset of artifact #%d failed: request does not appear to be valid',
                    $artifact->getId()
                )
            );
            return null;
        }

        $this->initializeAFakeChangesetSoThatListAndWorkflowEncounterAnEmptyState($artifact);
        if ($artifact->getTracker()->getWorkflow()->isDisabled() === false) {
            $workflow = $this->workflow_retriever->getNonNullWorkflow($artifact->getTracker());


            if (! $this->askWorkflowToUpdateTheRequestAndCheckGlobalRules($artifact, $fields_data, $submitter, $workflow)) {
                $this->logger->debug(
                    sprintf(
                        'Creation of the first changeset of artifact #%d failed: workflow/global rules rejected it',
                        $artifact->getId()
                    )
                );
                return null;
            }
        } else {
            $workflow = $this->workflow_retriever->getNonNullWorkflow($artifact->getTracker());
        }

        try {
            $changeset_id = $this->artifact_changeset_saver->saveChangeset($artifact, $submitter, $submitted_on, $import_config);
        } catch (Tracker_Artifact_Exception_CannotCreateNewChangeset $exception) {
            $this->logger->debug(
                sprintf(
                    'Creation of the first changeset of artifact #%d failed: DB failure',
                    $artifact->getId()
                )
            );
            return null;
        }

        $this->storeFieldsValues($artifact, $fields_data, $submitter, $changeset_id, $url_mapping, $workflow);

        $changeset = $artifact->getChangeset($changeset_id);
        assert($changeset !== null);
        $this->after_new_changeset_handler->handle(
            $artifact,
            $fields_data,
            $submitter,
            $workflow,
            $changeset,
            null
        );

        $artifact->clearChangesets();

        return $changeset_id;
    }

    private function storeFieldsValues(
        Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        int $changeset_id,
        CreatedFileURLMapping $url_mapping,
        \Workflow $workflow,
    ): void {
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            $this->save_initial_changeset_value->saveNewChangesetForField(
                $field,
                $artifact,
                $fields_data,
                $submitter,
                $changeset_id,
                $url_mapping,
                $workflow
            );
        }
    }

    private function doesRequestAppearToBeValid(
        Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        ChangesetValidationContext $changeset_validation_context,
    ): bool {
        if ($submitter->isAnonymous() && ! trim($submitter->getEmail())) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'You are not logged in.'));
            return false;
        }

        return $this->fields_validator->validate($artifact, $submitter, $fields_data, $changeset_validation_context);
    }

    private function askWorkflowToUpdateTheRequestAndCheckGlobalRules(
        Artifact $artifact,
        array &$fields_data,
        PFUser $submitter,
        \Workflow $workflow,
    ): bool {
        try {
            $workflow->validate($fields_data, $artifact, "", $submitter);
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

    private function initializeAFakeChangesetSoThatListAndWorkflowEncounterAnEmptyState(Artifact $artifact): void
    {
        $artifact->setChangesets([new Tracker_Artifact_Changeset_Null()]);
    }
}
