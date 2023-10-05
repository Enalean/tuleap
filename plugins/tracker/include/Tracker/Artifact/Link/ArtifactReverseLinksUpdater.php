<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Link;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class ArtifactReverseLinksUpdater
{
    public function __construct(
        private readonly RetrieveReverseLinks $reverse_links_retriever,
        private readonly ReverseLinksToNewChangesetsConverter $changesets_converter,
        private readonly CreateNewChangeset $changeset_creator,
    ) {
    }

    /**
     * @throws FieldValidationException
     * @throws \Tracker_Exception
     * @return Ok<null> | Err<Fault>
     */
    public function updateArtifactAndItsLinks(
        Artifact $artifact,
        ChangesetValuesContainer $changeset_values,
        \PFUser $submitter,
        \DateTimeImmutable $submission_date,
        NewComment $new_comment,
    ): Ok|Err {
        $result = $changeset_values->getArtifactLinkValue()
            ->andThen(fn(NewArtifactLinkChangesetValue $changeset_value): Option => $changeset_value->getSubmittedReverseLinks())
            ->mapOr(
                function (CollectionOfReverseLinks $submitted_reverse_links) use (
                    $submission_date,
                    $submitter,
                    $artifact
                ): Ok|Err {
                    $stored_reverse_links  = $this->reverse_links_retriever->retrieveReverseLinks(
                        $artifact,
                        $submitter
                    );
                    $reverse_links_command = ChangeReverseLinksCommand::fromSubmittedAndExistingLinks(
                        $artifact,
                        $submitted_reverse_links,
                        $stored_reverse_links
                    );
                    return $this->changesets_converter->convertChangeReverseLinks(
                        $reverse_links_command,
                        $submitter,
                        $submission_date
                    );
                },
                Result::ok([])
            );

        return $result->map(fn(array $new_changesets) => [
            ...$new_changesets,
            NewChangeset::fromFieldsDataArray(
                $artifact,
                $changeset_values->getFieldsData(),
                $new_comment->getBody(),
                $new_comment->getFormat(),
                $new_comment->getUserGroupsThatAreAllowedToSee(),
                $submitter,
                $submission_date->getTimestamp(),
                new CreatedFileURLMapping()
            ),
        ])->andThen($this->saveChangesets(...));
    }

    /**
     * @param list<NewChangeset> $new_changesets
     * @return Ok<null> | Err<Fault>
     * @throws FieldValidationException
     * @throws \Tracker_Exception
     */
    private function saveChangesets(array $new_changesets): Ok|Err
    {
        foreach ($new_changesets as $changeset) {
            try {
                $this->changeset_creator->create($changeset, PostCreationContext::withNoConfig(true));
            } catch (\Tracker_NoChangeException) {
                //Ignore, it should not stop the update
            }
        }
        return Result::ok(null);
    }
}
