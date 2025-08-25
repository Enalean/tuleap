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

use Tracker_NoChangeException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\NewComment;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NoChangeFault;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;

final readonly class ArtifactReverseLinksUpdater
{
    public function __construct(
        private RetrieveReverseLinks $reverse_links_retriever,
        private ReverseLinksToNewChangesetsConverter $changesets_converter,
        private CreateNewChangeset $changeset_creator,
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

        return $result->andThen(fn(array $new_changesets) => $this->saveChangesets(
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
            ...$new_changesets
        ));
    }

    /**
     * @return Ok<null> | Err<Fault>
     * @throws FieldValidationException
     * @throws \Tracker_Exception
     */
    private function saveChangesets(NewChangeset $source_artifact_new_changeset, NewChangeset ...$reverse_link_changesets): Ok|Err
    {
        $source_no_change = Option::nothing(Fault::class);
        try {
            $this->changeset_creator->create($source_artifact_new_changeset, PostCreationContext::withNoConfig(true));
        } catch (Tracker_NoChangeException $exception) {
            $source_no_change = Option::fromValue(NoChangeFault::build($exception));
        }
        $at_least_one_reverse_changed = false;
        foreach ($reverse_link_changesets as $changeset) {
            try {
                $this->changeset_creator->create($changeset, PostCreationContext::withNoConfig(true));
                $at_least_one_reverse_changed = true;
            } catch (Tracker_NoChangeException) {
                //Ignore, it should not stop the update
            }
        }
        return $this->returnNoChangeIfNoneOfTheReverseLinksHaveChanged($source_no_change, $at_least_one_reverse_changed);
    }

    /**
     * @param Option<Fault> $source_no_change
     * @return Ok<null>|Err<Fault>
     */
    private function returnNoChangeIfNoneOfTheReverseLinksHaveChanged(
        Option $source_no_change,
        bool $at_least_one_reverse_changed,
    ): Ok|Err {
        return $source_no_change->mapOr(
            static fn(Fault $no_change_fault): Ok|Err => $at_least_one_reverse_changed
                ? Result::ok(null)
                : Result::err($no_change_fault),
            Result::ok(null)
        );
    }
}
