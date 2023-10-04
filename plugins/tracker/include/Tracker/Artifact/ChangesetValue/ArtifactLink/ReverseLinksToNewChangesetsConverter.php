<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistFault;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveUsedArtifactLinkFields;

final class ReverseLinksToNewChangesetsConverter
{
    public function __construct(
        private readonly RetrieveUsedArtifactLinkFields $field_retriever,
        private readonly RetrieveViewableArtifact $artifact_retriever,
    ) {
    }

    /**
     * @return Ok<list<NewChangeset>> | Err<Fault>
     */
    public function convertAddReverseLinks(
        AddReverseLinksCommand $command,
        \PFUser $submitter,
        \DateTimeImmutable $submission_date,
    ): Ok|Err {
        $new_changesets = [];
        foreach ($command->getLinksToAdd()->links as $link_to_add) {
            $result = $this->convertLink(
                $link_to_add,
                $this->convertLinkToAdd(...),
                $command->getTargetArtifact(),
                $submitter,
                $submission_date
            );
            if (Result::isOk($result)) {
                $new_changesets[] = $result->value;
            } else {
                return $result;
            }
        }
        return Result::ok($new_changesets);
    }

    /**
     * @return Ok<list<NewChangeset>> | Err<Fault>
     */
    public function convertChangeReverseLinks(
        ChangeReverseLinksCommand $command,
        \PFUser $submitter,
        \DateTimeImmutable $submission_date,
    ): Ok|Err {
        $new_changesets = [];
        foreach ($command->getLinksToAdd()->links as $link_to_add) {
            $result = $this->convertLink(
                $link_to_add,
                $this->convertLinkToAdd(...),
                $command->getTargetArtifact(),
                $submitter,
                $submission_date
            );
            if (Result::isOk($result)) {
                $new_changesets[] = $result->value;
            } else {
                return $result;
            }
        }
        foreach ($command->getLinksToChange()->links as $link_to_change) {
            $result = $this->convertLink(
                $link_to_change,
                $this->convertLinkToChange(...),
                $command->getTargetArtifact(),
                $submitter,
                $submission_date
            );
            if (Result::isOk($result)) {
                $new_changesets[] = $result->value;
            } else {
                return $result;
            }
        }
        foreach ($command->getLinksToRemove()->links as $link_to_remove) {
            $result = $this->convertLink(
                $link_to_remove,
                $this->convertLinkToRemove(...),
                $command->getTargetArtifact(),
                $submitter,
                $submission_date
            );
            if (Result::isOk($result)) {
                $new_changesets[] = $result->value;
            } else {
                return $result;
            }
        }
        return Result::ok($new_changesets);
    }

    /**
     * @param callable(Artifact, ReverseLink, \Tracker_FormElement_Field_ArtifactLink): ChangeForwardLinksCommand $convertToForwardLinksCommand
     * @return Ok<NewChangeset> | Err<Fault>
     */
    private function convertLink(
        ReverseLink $reverse_link,
        callable $convertToForwardLinksCommand,
        Artifact $target_artifact,
        \PFUser $submitter,
        \DateTimeImmutable $submission_date,
    ): Ok|Err {
        return $this->getArtifactById($submitter, $reverse_link->getSourceArtifactId())
            ->andThen(fn(Artifact $source_artifact) => $this->getArtifactLinkField($source_artifact)
                ->map(
                    fn(
                        \Tracker_FormElement_Field_ArtifactLink $link_field,
                    ) => NewChangeset::fromFieldsDataArrayWithEmptyComment(
                        $source_artifact,
                        (new ChangesetValuesContainer(
                            [],
                            Option::fromValue(
                                NewArtifactLinkChangesetValue::fromOnlyForwardLinks(
                                    $convertToForwardLinksCommand($target_artifact, $reverse_link, $link_field)
                                )
                            )
                        ))->getFieldsData(),
                        $submitter,
                        $submission_date->getTimestamp()
                    )
                ));
    }

    private function convertLinkToAdd(
        Artifact $target_artifact,
        ReverseLink $link,
        \Tracker_FormElement_Field_ArtifactLink $source_link_field,
    ): ChangeForwardLinksCommand {
        return ChangeForwardLinksCommand::fromParts(
            $source_link_field->getId(),
            CollectionOfForwardLinks::fromReverseLink($target_artifact, $link),
            new CollectionOfForwardLinks([]),
            new CollectionOfForwardLinks([])
        );
    }

    private function convertLinkToChange(
        Artifact $target_artifact,
        ReverseLink $link,
        \Tracker_FormElement_Field_ArtifactLink $source_link_field,
    ): ChangeForwardLinksCommand {
        return ChangeForwardLinksCommand::fromParts(
            $source_link_field->getId(),
            new CollectionOfForwardLinks([]),
            CollectionOfForwardLinks::fromReverseLink($target_artifact, $link),
            new CollectionOfForwardLinks([])
        );
    }

    private function convertLinkToRemove(
        Artifact $target_artifact,
        ReverseLink $link,
        \Tracker_FormElement_Field_ArtifactLink $source_link_field,
    ): ChangeForwardLinksCommand {
        return ChangeForwardLinksCommand::fromParts(
            $source_link_field->getId(),
            new CollectionOfForwardLinks([]),
            new CollectionOfForwardLinks([]),
            CollectionOfForwardLinks::fromReverseLink($target_artifact, $link),
        );
    }

    /**
     * @return Ok<Artifact>|Err<Fault>
     */
    private function getArtifactById(\PFUser $submitter, int $artifact_id): Ok|Err
    {
        $artifact = $this->artifact_retriever->getArtifactByIdUserCanView($submitter, $artifact_id);
        if (! $artifact) {
            return Result::err(ArtifactDoesNotExistFault::build($artifact_id));
        }
        return Result::ok($artifact);
    }

    /**
     * @return Ok<\Tracker_FormElement_Field_ArtifactLink>|Err<Fault>
     */
    private function getArtifactLinkField(Artifact $artifact): Ok|Err
    {
        $artlink_fields = $this->field_retriever->getUsedArtifactLinkFields($artifact->getTracker());
        if (empty($artlink_fields)) {
            return Result::err(ArtifactLinkFieldDoesNotExistFault::build($artifact->getId()));
        }
        return Result::ok($artlink_fields[0]);
    }
}
