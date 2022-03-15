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
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\Changeset\Value\SaveChangesetValue;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinkToParentWithoutCurrentArtifactChangeException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Workflow\RetrieveWorkflow;

/**
 * I create a new changeset (update of an artifact)
 */
class NewChangesetCreator
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
        private ActionsRunner $post_creation_runner,
        private SaveChangesetValue $changeset_value_saver,
        private RetrieveWorkflow $workflow_retriever,
        private CommentCreator $comment_creator,
    ) {
    }

    /**
     * Update an artifact (means create a new changeset)
     * @param \ProjectUGroup[] $ugroups
     * @throws \Tracker_NoChangeException In the validation
     * @throws FieldValidationException
     *
     * @throws \Tracker_Exception In the validation
     */
    public function create(
        Artifact $artifact,
        array $fields_data,
        string $comment,
        \PFUser $submitter,
        int $submitted_on,
        bool $send_notification,
        string $comment_format,
        CreatedFileURLMapping $url_mapping,
        TrackerImportConfig $tracker_import_config,
        array $ugroups,
    ): ?\Tracker_Artifact_Changeset {
        $comment = trim($comment);

        $email = null;
        if ($submitter->isAnonymous()) {
            $email = $submitter->getEmail();
        }

        try {
            $new_changeset = $this->transaction_executor->execute(function () use (
                $artifact,
                $fields_data,
                $comment,
                $comment_format,
                $submitter,
                $submitted_on,
                $email,
                $url_mapping,
                $tracker_import_config,
                $ugroups
            ) {
                try {
                    $workflow = $this->workflow_retriever->getNonNullWorkflow($artifact->getTracker());
                    $this->validateNewChangeset($artifact, $fields_data, $comment, $submitter, $email, $workflow);

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
                            $submitted_on,
                            $tracker_import_config
                        );
                    } catch (\Tracker_Artifact_Exception_CannotCreateNewChangeset $exception) {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            dgettext('tuleap-tracker', 'Unable to update the artifact')
                        );
                        throw new \Tracker_ChangesetNotCreatedException();
                    }

                    $this->storeFieldsValues(
                        $artifact,
                        $previous_changeset,
                        $fields_data,
                        $submitter,
                        $changeset_id,
                        $workflow,
                        $url_mapping
                    );

                    $new_comment = $this->createNewComment(
                        $changeset_id,
                        $comment,
                        $comment_format,
                        $submitter,
                        $submitted_on,
                        $ugroups,
                        $url_mapping
                    );
                    $this->comment_creator->createComment($artifact, $new_comment);

                    $new_changeset = new \Tracker_Artifact_Changeset(
                        $changeset_id,
                        $artifact,
                        $submitter->getId(),
                        $submitted_on,
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
            if (! $tracker_import_config->isFromXml()) {
                $this->post_creation_runner->executePostCreationActions($new_changeset, $send_notification);
            }

            $this->event_manager->processEvent(new ArtifactUpdated($artifact, $submitter, $new_changeset));

            return $new_changeset;
        } catch (\PDOException $exception) {
            throw new \Tracker_ChangesetCommitException($exception);
        }
    }

    /**
     * @param \ProjectUGroup[] $user_groups_that_are_allowed_to_see
     */
    private function createNewComment(
        int $changeset_id,
        string $comment,
        string $comment_format,
        \PFUser $submitter,
        int $submission_timestamp,
        array $user_groups_that_are_allowed_to_see,
        CreatedFileURLMapping $url_mapping,
    ): NewComment {
        if ($comment_format === \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT) {
            return NewComment::fromText(
                $changeset_id,
                $comment,
                $submitter,
                $submission_timestamp,
                $user_groups_that_are_allowed_to_see
            );
        }
        if ($comment_format === \Tracker_Artifact_Changeset_Comment::HTML_COMMENT) {
            return NewComment::fromHTML(
                $changeset_id,
                $comment,
                $submitter,
                $submission_timestamp,
                $user_groups_that_are_allowed_to_see,
                $url_mapping
            );
        }
        // Default to CommonMark
        return NewComment::fromCommonMark(
            $changeset_id,
            $comment,
            $submitter,
            $submission_timestamp,
            $user_groups_that_are_allowed_to_see
        );
    }

    /**
     * @throws \Tracker_FieldValueNotStoredException
     */
    private function storeFieldsValues(
        Artifact $artifact,
        ?\Tracker_Artifact_Changeset $previous_changeset,
        array $fields_data,
        \PFUser $submitter,
        int $changeset_id,
        \Workflow $workflow,
        CreatedFileURLMapping $url_mapping,
    ): void {
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            if (
                ! $this->changeset_value_saver->saveNewChangesetForField(
                    $field,
                    $artifact,
                    $previous_changeset,
                    $fields_data,
                    $submitter,
                    $changeset_id,
                    $workflow,
                    $url_mapping
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
    private function validateNewChangeset(
        Artifact $artifact,
        array $fields_data,
        string $comment,
        \PFUser $submitter,
        ?string $email,
        \Workflow $workflow,
    ): void {
        if ($submitter->isAnonymous() && ($email === null || $email === '')) {
            $message = dgettext('tuleap-tracker', 'You are not logged in.');
            throw new \Tracker_Exception($message);
        }

        $are_fields_valid = $this->fields_validator->validate(
            $artifact,
            $submitter,
            $fields_data,
            new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
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

        $fields_data = $this->field_initializator->process($artifact, $fields_data);

        $workflow->validate($fields_data, $artifact, $comment, $submitter);
        /*
         * We need to run the post actions to validate the data
         */
        $workflow->before($fields_data, $submitter, $artifact);
        $workflow->checkGlobalRules($fields_data);
    }
}
