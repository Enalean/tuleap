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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;

final class ProgramIncrementCreationProcessor implements ProcessProgramIncrementCreation
{
    public function __construct(
        private RetrievePlanningMilestoneTracker $root_milestone_retriever,
        private ProgramIncrementsCreator $program_increment_creator,
        private LoggerInterface $logger,
        private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        private SearchTeamsOfProgram $teams_searcher,
        private BuildProject $project_builder,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder
    ) {
    }

    public function processCreation(ProgramIncrementCreation $creation): void
    {
        $this->logger->debug(
            sprintf(
                'Processing program increment creation with program increment #%d for user #%d',
                $creation->getProgramIncrement()->getId(),
                $creation->getUser()->getId()
            )
        );
        try {
            $this->create($creation);
        } catch (
            TrackerRetrievalException
            | MirroredTimeboxReplicationException
            | FieldSynchronizationException
            | ProgramAccessException
            | ProjectIsNotAProgramException $exception
        ) {
            $this->logger->error('Error during creation of mirror program increments ', ['exception' => $exception]);
        }
    }

    /**
     * @throws MirroredTimeboxReplicationException
     * @throws TrackerRetrievalException
     * @throws FieldRetrievalException
     * @throws FieldSynchronizationException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     */
    private function create(ProgramIncrementCreation $creation): void
    {
        $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
            $this->fields_gatherer,
            $this->values_retriever,
            $this->submission_date_retriever,
            $creation
        );

        $user    = $creation->getUserReference();
        $program = ProgramIdentifier::fromProgramIncrement(
            $this->program_retriever,
            $this->program_builder,
            $creation->getProgramIncrement(),
            $user
        );

        $team_projects = TeamProjectsCollection::fromProgramIdentifier(
            $this->teams_searcher,
            $this->project_builder,
            $program
        );

        $root_planning_tracker_team = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $this->root_milestone_retriever,
            $team_projects,
            $user,
            new ConfigurationErrorsCollector(false)
        );

        $this->program_increment_creator->createProgramIncrements(
            $source_values,
            $root_planning_tracker_team,
            $user
        );

        $program_increment_changed = new ProgramIncrementChanged(
            $creation->getProgramIncrement()->getId(),
            $creation->getTracker()->getId(),
            $user
        );

        $this->user_stories_planner->plan($program_increment_changed);
    }
}
