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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

/**
 * I am a Template Method to create a new changeset (update of an artifact)
 */
abstract class Tracker_Artifact_Changeset_NewChangesetCreatorBase extends Tracker_Artifact_Changeset_ChangesetCreatorBase //phpcs:ignore
{
    /** @var Tracker_Artifact_ChangesetDao */
    protected $changeset_dao;

    /** @var Tracker_Artifact_Changeset_CommentDao */
    protected $changeset_comment_dao;
    /**
     * @var SourceOfAssociationCollectionBuilder
     */
    private $source_of_association_collection_builder;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        Tracker_Artifact_ChangesetDao $changeset_dao,
        Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
        Tracker_ArtifactFactory $artifact_factory,
        EventManager $event_manager,
        ReferenceManager $reference_manager,
        SourceOfAssociationCollectionBuilder $source_of_association_collection_builder,
        Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator,
        DBTransactionExecutor $transaction_executor
    ) {
        parent::__construct(
            $fields_validator,
            $fields_retriever,
            $artifact_factory,
            $event_manager,
            $field_initializator
        );

        $this->changeset_dao                            = $changeset_dao;
        $this->changeset_comment_dao                    = $changeset_comment_dao;
        $this->reference_manager                        = $reference_manager;
        $this->source_of_association_collection_builder = $source_of_association_collection_builder;
        $this->transaction_executor                     = $transaction_executor;
    }

    /**
     * Update an artifact (means create a new changeset)
     *
     * @throws Tracker_NoChangeException In the validation
     * @throws FieldValidationException
     *
     * @throws Tracker_Exception In the validation
     */
    public function create(
        Tracker_Artifact $artifact,
        array $fields_data,
        string $comment,
        PFUser $submitter,
        int $submitted_on,
        bool $send_notification,
        string $comment_format,
        CreatedFileURLMapping $url_mapping
    ): ?Tracker_Artifact_Changeset {
        $comment = trim($comment);

        $email = null;
        if ($submitter->isAnonymous()) {
            $email = $submitter->getEmail();
        }

        try {
            $new_changeset = $this->transaction_executor->execute(function () use ($artifact, $fields_data, $comment, $comment_format, $submitter, $submitted_on, $email, $url_mapping) {
                try {
                    $this->validateNewChangeset($artifact, $fields_data, $comment, $submitter, $email);

                    $previous_changeset = $artifact->getLastChangeset();

                    /*
                     * Post actions were run by validateNewChangeset but they modified a
                     * different set of $fields_data in the case of massChange;
                     * we run them again for the current $fields_data
                     */
                    $artifact->getWorkflow()->before($fields_data, $submitter, $artifact);

                    $changeset_id = $this->changeset_dao->create(
                        $artifact->getId(),
                        $submitter->getId(),
                        $email,
                        $submitted_on
                    );
                    if (! $changeset_id) {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            $GLOBALS['Language']->getText('plugin_tracker_artifact', 'unable_update')
                        );
                        throw new Tracker_ChangesetNotCreatedException();
                    }

                    $this->storeFieldsValues(
                        $artifact,
                        $previous_changeset,
                        $fields_data,
                        $submitter,
                        $changeset_id,
                        $url_mapping
                    );

                    if (
                        ! $this->storeComment(
                            $artifact,
                            $comment,
                            $submitter,
                            $submitted_on,
                            $comment_format,
                            $changeset_id,
                            $url_mapping
                        )
                    ) {
                        throw new Tracker_CommentNotStoredException();
                    }

                    $new_changeset = new Tracker_Artifact_Changeset(
                        $changeset_id,
                        $artifact,
                        $submitter->getId(),
                        $submitted_on,
                        $email
                    );
                    $artifact->addChangeset($new_changeset);

                    $save_after_ok = $this->saveArtifactAfterNewChangeset(
                        $artifact,
                        $fields_data,
                        $submitter,
                        $new_changeset,
                        $previous_changeset
                    );

                    if (! $save_after_ok) {
                        throw new Tracker_AfterSaveException();
                    }
                    ArtifactInstrumentation::increment(ArtifactInstrumentation::TYPE_UPDATED);
                    return $new_changeset;
                } catch (Tracker_NoChangeException $exception) {
                    $collection = $this->source_of_association_collection_builder->getSourceOfAssociationCollection(
                        $artifact,
                        $fields_data
                    );
                    if (count($collection) > 0) {
                        $collection->linkToArtifact($artifact, $submitter);

                        return null;
                    }
                    throw $exception;
                } catch (Tracker_Exception $exception) {
                    throw $exception;
                }
            });


            if (! $new_changeset) {
                return null;
            }

            if ($send_notification) {
                $artifact->getChangeset($new_changeset->getId())->executePostCreationActions();
            }

            $this->event_manager->processEvent(new ArtifactUpdated($artifact, $submitter));

            return $new_changeset;
        } catch (PDOException $exception) {
            throw new Tracker_ChangesetCommitException($exception);
        }
    }

    abstract protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id,
        CreatedFileURLMapping $url_mapping
    ): bool;

    /**
     * @throws Tracker_FieldValueNotStoredException
     */
    private function storeFieldsValues(
        Tracker_Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id,
        CreatedFileURLMapping $url_mapping
    ): bool {
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            if (
                ! $this->saveNewChangesetForField(
                    $field,
                    $artifact,
                    $previous_changeset,
                    $fields_data,
                    $submitter,
                    $changeset_id,
                    $url_mapping
                )
            ) {
                $purifier = Codendi_HTMLPurifier::instance();
                throw new Tracker_FieldValueNotStoredException(
                    $GLOBALS['Language']->getText(
                        'plugin_tracker',
                        'field_not_stored_exception',
                        [$purifier->purify($field->getLabel())]
                    )
                );
            }
        }

        return true;
    }

    private function storeComment(
        Tracker_Artifact $artifact,
        $comment,
        PFUser $submitter,
        $submitted_on,
        $comment_format,
        $changeset_id,
        CreatedFileURLMapping $url_mapping
    ): bool {
        $comment_format = Tracker_Artifact_Changeset_Comment::checkCommentFormat($comment_format);

        if ($comment_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            $substitutor = new \Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor();
            $comment     = $substitutor->substituteURLsInHTML($comment, $url_mapping);
        }

        $comment_added = $this->changeset_comment_dao->createNewVersion(
            $changeset_id,
            $comment,
            $submitter->getId(),
            $submitted_on,
            0,
            $comment_format
        );
        if (! $comment_added) {
            return false;
        }

        $this->reference_manager->extractCrossRef(
            $comment,
            $artifact->getId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $artifact->getTracker()->getGroupID(),
            $submitter->getId(),
            $artifact->getTracker()->getItemName()
        );

        return true;
    }

    private function validateNewChangeset(
        Tracker_Artifact $artifact,
        array $fields_data,
        $comment,
        PFUser $submitter,
        $email
    ): bool {
        if ($submitter->isAnonymous() && ($email == null || $email == '')) {
            $message = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'email_required');
            throw new Tracker_Exception($message);
        }

        if (! $this->fields_validator->validate($artifact, $submitter, $fields_data)) {
            $errors_from_feedback = $GLOBALS['Response']->getFeedbackErrors();
            $GLOBALS['Response']->clearFeedbackErrors();

            throw new FieldValidationException($errors_from_feedback);
        }

        $last_changeset = $artifact->getLastChangeset();

        if (! $comment && ! $last_changeset->hasChanges($fields_data)) {
            throw new Tracker_NoChangeException($artifact->getId(), $artifact->getXRef());
        }

        $workflow    = $artifact->getWorkflow();
        $fields_data = $this->field_initializator->process($artifact, $fields_data);

        $workflow->validate($fields_data, $artifact, $comment);
        /*
         * We need to run the post actions to validate the data
         */
        $workflow->before($fields_data, $submitter, $artifact);
        $workflow->checkGlobalRules($fields_data);

        return true;
    }
}
