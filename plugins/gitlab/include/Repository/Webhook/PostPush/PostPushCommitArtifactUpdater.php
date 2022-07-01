<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Exception;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_NoChangeException;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\CreateCommentOnlyChangeset;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneNotDefinedException;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;

final class PostPushCommitArtifactUpdater
{
    public function __construct(
        private StatusValueRetriever $status_value_retriever,
        private DoneValueRetriever $done_value_retriever,
        private LoggerInterface $logger,
        private CreateCommentOnlyChangeset $comment_creator,
    ) {
    }

    /**
     * @throws \Tuleap\Tracker\Workflow\NoPossibleValueException
     */
    public function closeTuleapArtifact(
        Artifact $artifact,
        PFUser $tracker_workflow_user,
        PostPushCommitWebhookData $commit,
        \Tracker_Semantic_Status $status_semantic,
        string $closing_comment_body,
        NewComment $no_semantic_comment,
    ): void {
        try {
            if (! $artifact->isOpen()) {
                $this->logger->info(
                    "Artifact #{$artifact->getId()} is already closed and can not be closed automatically by GitLab commit #{$commit->getSha1()}"
                );
                return;
            }

            $status_field = $status_semantic->getField();
            if ($status_field === null) {
                $result = $this->comment_creator->createCommentOnlyChangeset($no_semantic_comment, $artifact);
                if (Result::isErr($result)) {
                    $this->logger->error((string) $result->error);
                }
                return;
            }

            try {
                $closed_value = $this->getClosedValue($artifact, $tracker_workflow_user);
            } catch (SemanticStatusClosedValueNotFoundException $e) {
                $result = $this->comment_creator->createCommentOnlyChangeset($no_semantic_comment, $artifact);
                if (Result::isErr($result)) {
                    $this->logger->error((string) $result->error);
                }
                return;
            }

            $fields_data = [
                $status_field->getId() => $status_field->getFieldData($closed_value->getLabel()),
            ];

            $new_followups = $artifact->createNewChangeset($fields_data, $closing_comment_body, $tracker_workflow_user);

            if ($new_followups === null) {
                $this->logger->error("No new comment was created");
            }
        } catch (Tracker_NoChangeException | Tracker_Exception $e) {
            $this->logger->error("An error occurred during the creation of the comment");
        }
    }

    /**
     * @throws \Tuleap\Tracker\Workflow\NoPossibleValueException
     * @throws SemanticStatusClosedValueNotFoundException
     */
    private function getClosedValue(Artifact $artifact, PFUser $tracker_workflow_user): Tracker_FormElement_Field_List_BindValue
    {
        try {
            return $this->done_value_retriever->getFirstDoneValueUserCanRead($artifact, $tracker_workflow_user);
        } catch (
            SemanticDoneNotDefinedException | SemanticDoneValueNotFoundException $exception
        ) {
            $this->logger->warning($exception->getMessage() . " Status semantic will be checked to close the artifact.");
        }

        return $this->status_value_retriever->getFirstClosedValueUserCanRead($tracker_workflow_user, $artifact);
    }
}
