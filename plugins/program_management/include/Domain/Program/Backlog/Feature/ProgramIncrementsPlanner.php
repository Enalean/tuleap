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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveLastChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

final class ProgramIncrementsPlanner implements PlanProgramIncrements
{
    public function __construct(
        private LogMessage $logger,
        private RetrieveMirroredProgramIncrementTracker $mirrored_tracker_retriever,
        private CreateProgramIncrements $program_increment_creator,
        private RetrieveProjectReference $project_retriever,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private ProcessIterationCreation $process_iteration_creation,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder,
        private RetrieveIterationTracker $iteration_tracker_retriever,
        private VerifyIsIteration $verify_is_iteration,
        private VerifyIsVisibleArtifact $verify_is_visible_artifact,
        private SearchIterations $search_iterations,
        private RetrieveLastChangeset $retrieve_last_changeset,
    ) {
    }

    #[\Override]
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

        $iterations_to_create = IterationCreation::fromProgramIncrementCreation(
            $this->program_retriever,
            $this->program_builder,
            $this->iteration_tracker_retriever,
            $this->verify_is_iteration,
            $this->verify_is_visible_artifact,
            $this->search_iterations,
            $this->retrieve_last_changeset,
            $creation
        );
        foreach ($iterations_to_create as $iteration_to_create) {
            $this->logger->debug(sprintf('Create iteration #%d for program increment #%d', $iteration_to_create->getIteration()->getId(), $iteration_to_create->getProgramIncrement()->getId()));
            $this->process_iteration_creation->processCreationForTeams($iteration_to_create, $teams);
        }

        return ProgramIncrementChanged::fromCreation($creation);
    }
}
