<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;

final class ProgramIncrementsPlanner implements PlanProgramIncrements
{
    public function __construct(
        private RetrieveMirroredProgramIncrementTracker $mirrored_tracker_retriever,
        private CreateProgramIncrements $program_increment_creator,
        private RetrieveProjectReference $project_retriever,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
    ) {
    }

    public function createProgramIncrementAndReturnPlanChange(ProgramIncrementCreation $creation, TeamIdentifierCollection $teams): ProgramIncrementChanged
    {
        $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
            $this->fields_gatherer,
            $this->values_retriever,
            $this->submission_date_retriever,
            $creation
        );

        $user = $creation->getUser();

        $mirrored_trackers = MirroredProgramIncrementTrackerIdentifierCollection::fromTeams(
            $this->mirrored_tracker_retriever,
            $this->project_retriever,
            $teams,
            $user
        );

        $this->program_increment_creator->createProgramIncrements(
            $source_values,
            $mirrored_trackers,
            $user
        );

        return ProgramIncrementChanged::fromCreation($creation);
    }
}
