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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MirroredProgramIncrementChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;

final class ArtifactCreatorAdapter implements CreateArtifact
{
    public function __construct(private TrackerArtifactCreator $artifact_creator, private \TrackerFactory $tracker_factory)
    {
    }

    /**
     * @throws ArtifactCreationException
     */
    public function create(
        ProgramTracker $tracker,
        MirroredProgramIncrementChangeset $mirrored_program_increment_changeset,
        \PFUser $user,
        SubmissionDate $submission_date
    ): void {
        $full_tracker = $this->tracker_factory->getTrackerById($tracker->getTrackerId());
        if (! $full_tracker) {
            throw new \RuntimeException("Tracker with id #" . $tracker->getTrackerId() . " is not found.");
        }
        $artifact = $this->artifact_creator->create(
            $full_tracker,
            $mirrored_program_increment_changeset->toFieldsDataArray(),
            $user,
            $submission_date->getValue(),
            false,
            false,
            new ChangesetWithFieldsValidationContext(new SystemActionContext())
        );
        if (! $artifact) {
            throw new ArtifactCreationException();
        }
    }
}
