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
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveReverseLinks;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\Link\HandleUpdateArtifact;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\FaultMapper;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class PUTHandler
{
    public function __construct(
        private FieldsDataBuilder $fields_data_builder,
        private RetrieveReverseLinks $reverse_links_retriever,
        private HandleUpdateArtifact $artifact_update_handler,
        private DBTransactionExecutor $transaction_executor,
        private CheckArtifactRestUpdateConditions $check_artifact_rest_update_conditions,
    ) {
    }

    /**
     * @param ArtifactValuesRepresentation[] $values
     * @throws RestException
     */
    public function handle(array $values, Artifact $artifact, \PFUser $submitter, ?NewChangesetCommentRepresentation $comment): void
    {
        try {
            $this->check_artifact_rest_update_conditions->checkIfArtifactUpdateCanBePerformedThroughREST($submitter, $artifact);
            $this->transaction_executor->execute(
                function () use ($artifact, $submitter, $comment, $values) {
                    $changeset_values = $this->fields_data_builder->getFieldsDataOnUpdate($values, $artifact, $submitter);

                    $changeset_values->getArtifactLinkValue()->apply(
                        function (NewArtifactLinkChangesetValue $artifact_link_value) use (
                            $submitter,
                            $artifact,
                            $values
                        ): void {
                            if (
                                $artifact_link_value->getNewParentLink()->isNothing()
                                && ! $this->isLinkKeyUsed($values)
                            ) {
                                $stored_reverse_links    = $this->reverse_links_retriever->retrieveReverseLinks($artifact, $submitter);
                                $reverse_link_collection = $artifact_link_value->getSubmittedReverseLinks();
                                $this->artifact_update_handler->updateTypeAndAddReverseLinks(
                                    $artifact,
                                    $submitter,
                                    $reverse_link_collection->differenceById($stored_reverse_links),
                                    $stored_reverse_links->differenceByType($reverse_link_collection)
                                )->map(
                                    fn() => $this->artifact_update_handler->removeReverseLinks(
                                        $artifact,
                                        $submitter,
                                        $stored_reverse_links->differenceById($reverse_link_collection)
                                    )
                                )->mapErr(FaultMapper::mapToRestException(...));
                            }
                        }
                    );

                    try {
                        $this->artifact_update_handler->updateForwardLinks($artifact, $submitter, $changeset_values, $comment);
                    } catch (Tracker_NoChangeException) {
                        //Do nothing
                    }
                }
            );
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

    private function isLinkKeyUsed(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value->links)) {
                return true;
            }
        }
        return false;
    }
}
