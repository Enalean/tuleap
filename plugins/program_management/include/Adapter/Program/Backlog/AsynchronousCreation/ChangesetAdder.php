<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangesetException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\NewChangesetCreationException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\ArtifactLinkChangeset;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;

final class ChangesetAdder implements AddChangeset, AddArtifactLinkChangeset
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
        private RetrieveUser $user_retriever,
        private ChangesetValuesFormatter $formatter,
        private NewChangesetCreator $new_changeset_creator,
    ) {
    }

    #[\Override]
    public function addChangeset(MirroredTimeboxChangeset $changeset): void
    {
        $full_artifact    = $this->artifact_retriever->getNonNullArtifact($changeset->mirrored_timebox);
        $full_user        = $this->user_retriever->getUserWithId($changeset->user);
        $formatted_values = $this->formatter->formatForTrackerPlugin($changeset->values);
        try {
            $this->createChangeset(
                $full_artifact,
                $full_user,
                $changeset->submission_date->getValue(),
                $formatted_values
            );
        } catch (FieldValidationException | \Tracker_Exception $exception) {
            throw new NewChangesetCreationException($changeset->mirrored_timebox->getId(), $exception);
        }
    }

    #[\Override]
    public function addArtifactLinkChangeset(ArtifactLinkChangeset $changeset): void
    {
        $full_artifact    = $this->artifact_retriever->getNonNullArtifact($changeset->mirrored_program_increment);
        $full_user        = $this->user_retriever->getUserWithId($changeset->user);
        $formatted_values = $this->formatter->formatArtifactLink(
            $changeset->artifact_link_field,
            $changeset->artifact_link_value
        );
        $current_time     = new \DateTimeImmutable();
        try {
            $this->createChangeset($full_artifact, $full_user, $current_time->getTimestamp(), $formatted_values);
        } catch (FieldValidationException | \Tracker_Exception $exception) {
            throw new AddArtifactLinkChangesetException($changeset->mirrored_program_increment, $exception);
        }
    }

    /**
     * @throws FieldValidationException
     * @throws \Tracker_Exception
     */
    private function createChangeset(Artifact $artifact, \PFUser $user, int $timestamp, array $formatted_values): void
    {
        $new_changeset = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            $artifact,
            $formatted_values,
            $user,
            $timestamp
        );
        try {
            $this->new_changeset_creator->create($new_changeset, PostCreationContext::withNoConfig(false));
        } catch (\Tracker_NoChangeException $e) {
            // Ignore the exception if there is no new change in the changeset. It means the changes are already done.
        }
    }
}
