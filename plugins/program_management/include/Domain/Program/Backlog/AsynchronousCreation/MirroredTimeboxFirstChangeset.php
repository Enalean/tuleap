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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold all the information to create an initial (not additional) changeset in a Mirrored Timebox
 * Artifact.
 * @see MirroredTimeboxChangeset
 * @psalm-immutable
 */
final class MirroredTimeboxFirstChangeset
{
    private function __construct(
        public TrackerIdentifier $mirrored_timebox_tracker,
        public MirroredTimeboxChangesetValues $values,
        public UserIdentifier $user,
        public SubmissionDate $submission_date,
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     */
    public static function fromMirroredTimeboxTracker(
        GatherSynchronizedFields $fields_gatherer,
        MapStatusByValue $status_mapper,
        TrackerIdentifier $mirrored_timebox_tracker,
        SourceTimeboxChangesetValues $source_values,
        ArtifactLinkValue $artifact_link_value,
        UserIdentifier $user,
    ): self {
        $fields = SynchronizedFieldReferences::fromTrackerIdentifier(
            $fields_gatherer,
            $mirrored_timebox_tracker,
            null
        );
        $values = MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
            $status_mapper,
            $source_values,
            $fields,
            $artifact_link_value
        );
        return new self($mirrored_timebox_tracker, $values, $user, $source_values->getSubmittedOn());
    }
}
