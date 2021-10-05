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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfIteration;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;

final class IterationCreationProcessor implements ProcessIterationCreation
{
    public function __construct(
        private LoggerInterface $logger,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private RetrieveProgramOfIteration $program_retriever,
        private BuildProgram $program_builder,
        private SearchTeamsOfProgram $teams_searcher,
        private RetrieveProjectReference $project_reference_retriever,
        private RetrieveMirroredIterationTracker $milestone_tracker_retriever
    ) {
    }

    public function processCreation(IterationCreation $iteration_creation): void
    {
        $this->logger->debug(
            sprintf(
                'Processing iteration creation with iteration #%d for user #%d',
                $iteration_creation->getIteration()->getId(),
                $iteration_creation->getUser()->getId()
            )
        );
        try {
            $this->create($iteration_creation);
        } catch (
            FieldSynchronizationException
            | MirroredTimeboxReplicationException
            | ProgramAccessException
            | ProjectIsNotAProgramException
            | TrackerRetrievalException $exception
        ) {
            $this->logger->error('Error during creation of mirror iterations', ['exception' => $exception]);
            return;
        }
    }

    /**
     * @throws MirroredTimeboxReplicationException
     * @throws FieldSynchronizationException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     * @throws TrackerRetrievalException
     */
    private function create(IterationCreation $creation): void
    {
        $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
            $this->fields_gatherer,
            $this->values_retriever,
            $this->submission_date_retriever,
            $creation
        );

        $user    = $creation->getUser();
        $program = ProgramIdentifier::fromIteration(
            $this->program_retriever,
            $this->program_builder,
            $creation->getIteration(),
            $user
        );

        $team_projects = TeamProjectsCollection::fromProgramIdentifier(
            $this->teams_searcher,
            $this->project_reference_retriever,
            $program
        );

        $second_planning_trackers = TrackerCollection::buildSecondPlanningMilestoneTracker(
            $this->milestone_tracker_retriever,
            $team_projects,
            $user,
            new ConfigurationErrorsCollector(false)
        );

        $this->logger->debug(sprintf('Title value: %s', $source_values->getTitleValue()->getValue()));
        foreach ($second_planning_trackers->getTrackers() as $second_planning_tracker) {
            $this->logger->debug(sprintf('Mirrored Iteration tracker: %s', $second_planning_tracker->getLabel()));
        }
    }
}
