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
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLink;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class PUTHandler
{
    public function __construct(
        private FieldsDataBuilder $fields_data_builder,
        private ArtifactUpdater $artifact_updater,
        private RetrieveReverseLinks $reverse_links_retriever,
    ) {
    }

    /**
     * @param ArtifactValuesRepresentation[] $values
     * @throws RestException
     */
    public function handle(array $values, Artifact $artifact, \PFUser $submitter, ?NewChangesetCommentRepresentation $comment): void
    {
        try {
            $changeset_values        = $this->fields_data_builder->getFieldsDataOnUpdate($values, $artifact, $submitter);
            $reverse_link_collection = $changeset_values->getArtifactLinkValue()?->getSubmittedReverseLinks();
            if ($reverse_link_collection !== null && count($reverse_link_collection->links) > 0) {
                $stored_reverse_links = $this->reverse_links_retriever->retrieveReverseLinks($artifact, $submitter);

                $stored_links_to_json = array_map(static fn(ReverseLink $stored_link) => sprintf('Link from %d with type `%s`', $stored_link->getSourceArtifactId(), $stored_link->getType() ?? ''), $stored_reverse_links->links);
                $links_to_json        = array_map(
                    static fn($link) => sprintf('Link from %d with type `%s`', $link->getSourceArtifactId(), $link->getType() ?? ''),
                    $reverse_link_collection->links
                );
                echo sprintf('{"Reverse link stored from artifact %d ": "%s", "Reverse links": "%s"}', $artifact->getId(), implode(', ', $stored_links_to_json), implode(', ', $links_to_json));
            } else {
                $this->artifact_updater->update($submitter, $artifact, $values, $comment);
            }
        } catch (Tracker_FormElement_InvalidFieldException | Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
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
}
