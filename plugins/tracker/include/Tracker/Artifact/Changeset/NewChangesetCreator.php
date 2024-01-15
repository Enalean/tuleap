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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreation;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuer;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\SaveChangesetValue;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinkToParentWithoutCurrentArtifactChangeException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\Workflow\RetrieveWorkflow;

/**
 * I create a new changeset (update of an artifact)
 */
class NewChangesetCreator implements CreateNewChangeset
{
    public function __construct(
        private \Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        private FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        private \EventManager $event_manager,
        private \Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator,
        private DBTransactionExecutor $transaction_executor,
        private ArtifactChangesetSaver $artifact_changeset_saver,
        private ParentLinkAction $parent_link_action,
        private AfterNewChangesetHandler $after_new_changeset_handler,
        private PostCreationActionsQueuer $post_creation_queuer,
        private SaveChangesetValue $changeset_value_saver,
        private RetrieveWorkflow $workflow_retriever,
        private CommentCreator $comment_creator,
    ) {
    }

    public function create(NewChangeset $changeset, PostCreationContext $context): ?\Tracker_Artifact_Changeset
    {
        $submitter = $changeset->getSubmitter();
        $email     = null;
        if ($submitter->isAnonymous()) {
            $email = $submitter->getEmail();
        }
        $artifact      = $changeset->getArtifact();
        $old_changeset = $artifact->getLastChangeset();

        try {
            $new_changeset = $this->transaction_executor->execute(function () use (
                $artifact,
                $submitter,
                $changeset,
                $context,
                $email,
            ) {
                $fields_data = $changeset->getFieldsData();

                try {
                    $workflow = $this->workflow_retriever->getNonNullWorkflow($artifact->getTracker());
                    $this->validateNewChangeset($changeset, $email, $workflow);

                    $previous_changeset = $artifact->getLastChangeset();

                    /*
                     * Post actions were run by validateNewChangeset but they modified a
                     * different set of $fields_data in the case of massChange;
                     * we run them again for the current $fields_data
                     */
                    $workflow->before($fields_data, $submitter, $artifact);

                    try {
                        $changeset_id = $this->artifact_changeset_saver->saveChangeset(
                            $artifact,
                            $submitter,
                            $changeset->getSubmissionTimestamp(),
                            $context->getImportConfig()
                        );
                    } catch (\Tracker_Artifact_Exception_CannotCreateNewChangeset $exception) {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            dgettext('tuleap-tracker', 'Unable to update the artifact')
                        );
                        throw new \Tracker_ChangesetNotCreatedException();
                    }

                    $this->storeFieldsValues(
                        $changeset,
                        $previous_changeset,
                        $fields_data,
                        $changeset_id,
                        $workflow
                    );

                    $comment_creation = CommentCreation::fromNewComment(
                        $changeset->getComment(),
                        $changeset_id,
                        $changeset->getUrlMapping()
                    );
                    $this->comment_creator->createComment($artifact, $comment_creation);

                    $new_changeset = new \Tracker_Artifact_Changeset(
                        $changeset_id,
                        $artifact,
                        $submitter->getId(),
                        $changeset->getSubmissionTimestamp(),
                        $email
                    );
                    $artifact->addChangeset($new_changeset);

                    $save_after_ok = $this->after_new_changeset_handler->handle(
                        $artifact,
                        $fields_data,
                        $submitter,
                        $workflow,
                        $new_changeset,
                        $previous_changeset
                    );

                    if (! $save_after_ok) {
                        throw new \Tracker_AfterSaveException();
                    }
                    ArtifactInstrumentation::increment(ArtifactInstrumentation::TYPE_UPDATED);
                    return $new_changeset;
                } catch (\Tracker_NoChangeException $exception) {
                    throw $exception;
                } catch (LinkToParentWithoutCurrentArtifactChangeException $exception) {
                    return null;
                } catch (\Tracker_Exception $exception) {
                    throw $exception;
                }
            });

            if (! $new_changeset) {
                return null;
            }
            if (! $context->getImportConfig()->isFromXml()) {
                $this->post_creation_queuer->queuePostCreation(
                    $new_changeset,
                    $context->shouldSendNotifications()
                );
            }

            if (! $old_changeset) {
                $old_changeset = $new_changeset;
            }
            $this->event_manager->processEvent(new ArtifactUpdated($artifact, $submitter, $new_changeset, $old_changeset));

            return $new_changeset;
        } catch (\PDOException $exception) {
            throw new \Tracker_ChangesetCommitException($exception);
        }
    }

    /**
     * @throws \Tracker_FieldValueNotStoredException
     */
    private function storeFieldsValues(
        NewChangeset $new_changeset,
        ?\Tracker_Artifact_Changeset $previous_changeset,
        array $fields_data,
        int $changeset_id,
        \Workflow $workflow,
    ): void {
        $artifact = $new_changeset->getArtifact();
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            if (
                ! $this->changeset_value_saver->saveNewChangesetForField(
                    $field,
                    $artifact,
                    $previous_changeset,
                    $fields_data,
                    $new_changeset->getSubmitter(),
                    $changeset_id,
                    $workflow,
                    $new_changeset->getUrlMapping()
                )
            ) {
                $purifier = \Codendi_HTMLPurifier::instance();
                throw new \Tracker_FieldValueNotStoredException(
                    sprintf(
                        dgettext('tuleap-tracker', 'The field "%1$s" cannot be stored.'),
                        $purifier->purify($field->getLabel())
                    )
                );
            }
        }
    }

    /**
     * @throws \Tracker_Workflow_Transition_InvalidConditionForTransitionException
     * @throws \Tracker_Workflow_GlobalRulesViolationException
     * @throws FieldValidationException
     * @throws \Tracker_NoChangeException
     * @throws \Tracker_Exception
     * @throws LinkToParentWithoutCurrentArtifactChangeException
     */
    private function validateNewChangeset(NewChangeset $new_changeset, ?string $email, \Workflow $workflow): void
    {
        $artifact    = $new_changeset->getArtifact();
        $submitter   = $new_changeset->getSubmitter();
        $fields_data = $new_changeset->getFieldsData();
        $comment     = $new_changeset->getComment()->getBody();
        if ($submitter->isAnonymous() && ($email === null || $email === '')) {
            $message = dgettext('tuleap-tracker', 'You are not logged in.');
            throw new \Tracker_Exception($message);
        }

        $are_fields_valid = $this->fields_validator->validate(
            $artifact,
            $submitter,
            $fields_data,
            new NullChangesetValidationContext()
        );
        if (! $are_fields_valid) {
            $errors_from_feedback = $GLOBALS['Response']->getFeedbackErrors();
            $GLOBALS['Response']->clearFeedbackErrors();

            throw new FieldValidationException($errors_from_feedback);
        }

        $last_changeset = $artifact->getLastChangeset();

        if ($last_changeset && ! $comment && ! $last_changeset->hasChanges($fields_data)) {
            if ($this->parent_link_action->linkParent($artifact, $submitter, $fields_data)) {
                throw new LinkToParentWithoutCurrentArtifactChangeException();
            }
            throw new \Tracker_NoChangeException($artifact->getId(), $artifact->getXRef());
        }

        $initialized_fields_data = $this->field_initializator->process($artifact, $fields_data);

        $workflow->validate($initialized_fields_data, $artifact, $comment, $submitter);
        /*
         * We need to run the post actions to validate the data
         */
        $workflow->before($initialized_fields_data, $submitter, $artifact);
        $workflow->checkGlobalRules($initialized_fields_data);
    }
}
