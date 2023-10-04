<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_NoChangeException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentContentNotValidException;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\Link\ArtifactReverseLinksUpdater;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\FaultMapper;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class PUTHandler
{
    public function __construct(
        private readonly FieldsDataBuilder $fields_data_builder,
        private readonly ArtifactReverseLinksUpdater $links_updater,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly CheckArtifactRestUpdateConditions $check_artifact_rest_update_conditions,
    ) {
    }

    /**
     * @param ArtifactValuesRepresentation[] $values
     * @throws RestException
     */
    public function handle(
        array $values,
        Artifact $artifact,
        \PFUser $submitter,
        ?NewChangesetCommentRepresentation $comment,
    ): void {
        try {
            $this->check_artifact_rest_update_conditions->checkIfArtifactUpdateCanBePerformedThroughREST(
                $submitter,
                $artifact
            );
            $this->transaction_executor->execute(function () use ($artifact, $submitter, $comment, $values) {
                $changeset_values = $this->fields_data_builder->getFieldsDataOnUpdate($values, $artifact, $submitter);

                $submission_date = new \DateTimeImmutable();
                $this->links_updater->updateArtifactAndItsLinks(
                    $artifact,
                    $changeset_values,
                    $submitter,
                    $submission_date,
                    $this->buildNewComment($comment, $submitter, $submission_date)
                )->mapErr(FaultMapper::mapToRestException(...));
            });
        } catch (Tracker_FormElement_InvalidFieldException | Tracker_FormElement_InvalidFieldValueException | CommentContentNotValidException | FieldValidationException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException) {
            //Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
    }

    private function buildNewComment(
        ?NewChangesetCommentRepresentation $comment,
        \PFUser $submitter,
        \DateTimeImmutable $submission_date,
    ): NewComment {
        if (! $comment) {
            return NewComment::buildEmpty($submitter, $submission_date->getTimestamp());
        }
        return NewComment::fromParts(
            $comment->body,
            CommentFormatIdentifier::fromFormatString($comment->format),
            $submitter,
            $submission_date->getTimestamp(),
            []
        );
    }
}
