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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredTimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold all the information to create an additional (not initial) changeset in a Mirrored Timebox
 * Artifact.
 * @see MirroredTimeboxFirstChangeset
 * @psalm-immutable
 */
final class MirroredTimeboxChangeset
{
    private function __construct(
        public MirroredTimeboxIdentifier $mirrored_timebox,
        public MirroredTimeboxChangesetValues $values,
        public UserIdentifier $user,
        public SubmissionDate $submission_date,
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     */
    public static function fromMirroredTimebox(
        RetrieveTrackerOfArtifact $tracker_retriever,
        GatherSynchronizedFields $fields_gatherer,
        MapStatusByValue $status_mapper,
        MirroredTimeboxIdentifier $timebox,
        SourceTimeboxChangesetValues $source_values,
        UserIdentifier $user,
    ): self {
        $mirror_tracker = $tracker_retriever->getTrackerOfArtifact($timebox);
        $fields         = SynchronizedFieldReferences::fromTrackerIdentifier(
            $fields_gatherer,
            $mirror_tracker,
            null
        );
        $values         = MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
            $status_mapper,
            $source_values,
            $fields,
            null
        );
        return new self($timebox, $values, $user, $source_values->getSubmittedOn());
    }
}
