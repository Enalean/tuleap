<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AddReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ConvertAddReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\REST\FaultMapper;

final class ReverseLinksAdder implements AddReverseLinks
{
    public function __construct(
        private readonly ConvertAddReverseLinks $changesets_converter,
        private readonly CreateNewChangeset $changeset_creator,
    ) {
    }

    public function addReverseLinks(
        PFUser $submitter,
        InitialChangesetValuesContainer $changeset_values,
        Artifact $artifact,
    ): void {
        $changeset_values->getArtifactLinkValue()->apply(
            function (NewArtifactLinkInitialChangesetValue $artifact_link_value) use (
                $submitter,
                $artifact
            ): void {
                if ($artifact_link_value->getParent()->isNothing()) {
                    $submission_date = new \DateTimeImmutable();
                    $this->changesets_converter->convertAddReverseLinks(
                        AddReverseLinksCommand::fromParts($artifact, $artifact_link_value->getReverseLinks()),
                        $submitter,
                        $submission_date
                    )->match(
                        $this->saveChangesets(...),
                        FaultMapper::mapToRestException(...)
                    );
                }
            }
        );
    }

    /**
     * @param list<NewChangeset> $new_changesets
     * @throws \Tracker_Exception
     * @throws \Tuleap\Tracker\Artifact\Exception\FieldValidationException
     */
    private function saveChangesets(array $new_changesets): void
    {
        foreach ($new_changesets as $changeset) {
            try {
                $this->changeset_creator->create($changeset, PostCreationContext::withNoConfig(true));
            } catch (\Tracker_NoChangeException) {
                //Ignore, it should not stop the update
            }
        }
    }
}
