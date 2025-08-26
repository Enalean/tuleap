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

namespace Tuleap\Tracker\Artifact\Closure;

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Exception;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_NoChangeException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\CreateCommentOnlyChangeset;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\Semantic\Status\Done\DoneValueRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneNotDefinedException;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatusField;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

final class ArtifactCloser
{
    public function __construct(
        private RetrieveSemanticStatusField $status_retriever,
        private StatusValueRetriever $status_value_retriever,
        private DoneValueRetriever $done_value_retriever,
        private LoggerInterface $logger,
        private CreateCommentOnlyChangeset $comment_creator,
        private CreateNewChangeset $changeset_creator,
    ) {
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    public function closeArtifact(
        Artifact $artifact,
        PFUser $tracker_workflow_user,
        ArtifactClosingCommentInCommonMarkFormat $closing_comment_body,
        BadSemanticCommentInCommonMarkFormat $bad_semantic_comment_body,
    ): Ok|Err {
        if (! $artifact->isOpen()) {
            return Result::err(ArtifactIsAlreadyClosedFault::build());
        }

        $status_field = $this->status_retriever->fromTracker($artifact->getTracker());
        if ($status_field === null) {
            return $this->addBadSemanticComment($bad_semantic_comment_body, $artifact, $tracker_workflow_user);
        }

        try {
            $closed_value = $this->getClosedValue($artifact, $tracker_workflow_user);
        } catch (SemanticStatusClosedValueNotFoundException $e) {
            return $this->addBadSemanticComment($bad_semantic_comment_body, $artifact, $tracker_workflow_user);
        } catch (NoPossibleValueException $e) {
            return Result::err(
                Fault::fromThrowableWithMessage(
                    $e,
                    sprintf('Artifact #%d cannot be closed. %s', $artifact->getId(), $e->getMessage())
                )
            );
        }

        $fields_data = [
            $status_field->getId() => $status_field->getFieldData($closed_value->getLabel()),
        ];

        try {
            $new_followups = $this->changeset_creator->create(
                NewChangeset::fromFieldsDataArray(
                    $artifact,
                    $fields_data,
                    $closing_comment_body->getBody(),
                    CommentFormatIdentifier::COMMONMARK,
                    [],
                    $tracker_workflow_user,
                    (new \DateTimeImmutable())->getTimestamp(),
                    new CreatedFileURLMapping()
                ),
                PostCreationContext::withNoConfig(true)
            );

            if ($new_followups === null) {
                return Result::err(Fault::fromMessage('No new comment was created'));
            }
        } catch (Tracker_NoChangeException | Tracker_Exception $e) {
            return Result::err(
                Fault::fromThrowableWithMessage(
                    $e,
                    sprintf('An error occurred during the creation of the comment: %s', $e->getMessage())
                )
            );
        }
        return Result::ok(null);
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
            $this->logger->warning($exception->getMessage() . ' Status semantic will be checked to close the artifact.');
        }

        return $this->status_value_retriever->getFirstClosedValueUserCanRead($tracker_workflow_user, $artifact);
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    private function addBadSemanticComment(
        BadSemanticCommentInCommonMarkFormat $comment_body,
        Artifact $artifact,
        \PFUser $tracker_workflow_user,
    ): Err|Ok {
        $no_semantic_comment = NewComment::fromParts(
            $comment_body->getBody(),
            CommentFormatIdentifier::COMMONMARK,
            $tracker_workflow_user,
            (new \DateTimeImmutable())->getTimestamp(),
            []
        );

        return $this->comment_creator->createCommentOnlyChangeset($no_semantic_comment, $artifact)
            ->map(static fn() => null);
    }
}
