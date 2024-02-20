<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxIdentifierProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredTimeboxIdentifier;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;

final class ArtifactCreatorAdapter implements CreateArtifact
{
    public function __construct(
        private TrackerArtifactCreator $artifact_creator,
        private RetrieveFullTracker $tracker_retriever,
        private RetrieveUser $retrieve_user,
        private ChangesetValuesFormatter $formatter,
    ) {
    }

    public function create(MirroredTimeboxFirstChangeset $first_changeset): MirroredTimeboxIdentifier
    {
        $full_tracker = $this->tracker_retriever->getNonNullTracker($first_changeset->mirrored_timebox_tracker);

        $pfuser           = $this->retrieve_user->getUserWithId($first_changeset->user);
        $formatted_values = $this->formatter->formatForTrackerPlugin($first_changeset->values);
        $artifact         = $this->artifact_creator->create(
            $full_tracker,
            new InitialChangesetValuesContainer($formatted_values, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $pfuser,
            $first_changeset->submission_date->getValue(),
            false,
            false,
            new ChangesetWithFieldsValidationContext(new SystemActionContext()),
            false,
        );
        if (! $artifact) {
            throw new ArtifactCreationException();
        }
        return MirroredTimeboxIdentifierProxy::fromArtifact($artifact);
    }
}
