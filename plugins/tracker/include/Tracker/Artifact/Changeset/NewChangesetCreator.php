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
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinkToParentWithoutCurrentArtifactChangeException;
use Tuleap\Tracker\Workflow\RetrieveWorkflow;

/**
 * I create a new changeset (update of an artifact)
 */
class NewChangesetCreator implements CreateNewChangeset
{
    public function __construct(
        private DBTransactionExecutor $transaction_executor,
        private ArtifactChangesetSaver $artifact_changeset_saver,
        private AfterNewChangesetHandler $after_new_changeset_handler,
        private RetrieveWorkflow $workflow_retriever,
        private CommentCreator $comment_creator,
        private StoreNewChangesetFieldValues $store_new_changeset_field_values,
        private ValidateNewChangeset $validate_new_changeset,
        private ProcessChangesetPostCreation $changeset_created_post_process_creation,
    ) {
    }

    #[\Override]
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
            $changeset_created = $this->transaction_executor->execute(function () use (
                $artifact,
                $submitter,
                $changeset,
                $context,
                $email,
            ) {
                $fields_data = $changeset->getFieldsData();

                try {
                    $workflow = $this->workflow_retriever->getNonNullWorkflow($artifact->getTracker());
                    $this->validate_new_changeset->validateNewChangeset($changeset, $email, $workflow);

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

                    $this->store_new_changeset_field_values->storeFieldsValues(
                        $changeset,
                        $previous_changeset,
                        $fields_data,
                        $changeset_id,
                        $workflow
                    );

                    $comment_creation  = CommentCreation::fromNewComment(
                        $changeset->getComment(),
                        $changeset_id,
                        $changeset->getUrlMapping()
                    );
                    $should_update_fts = $this->comment_creator->createComment($artifact, $comment_creation);

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
                    return new NewChangesetCreated($new_changeset, $should_update_fts, $comment_creation);
                } catch (\Tracker_NoChangeException $exception) {
                    throw $exception;
                } catch (LinkToParentWithoutCurrentArtifactChangeException $exception) {
                    return null;
                } catch (\Tracker_Exception $exception) {
                    throw $exception;
                }
            });

            if (! $changeset_created) {
                return null;
            }
            assert($changeset_created instanceof NewChangesetCreated);

            $new_changeset = $changeset_created->changeset;

            $this->changeset_created_post_process_creation->postProcessCreation($changeset_created, $artifact, $context, $old_changeset, $submitter);

            return $new_changeset;
        } catch (\PDOException $exception) {
            throw new \Tracker_ChangesetCommitException($exception);
        }
    }
}
