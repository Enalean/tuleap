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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateTaskProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;

final class CreateProgramIncrementsTask implements CreateTaskProgramIncrement
{
    private ProgramIncrementsCreator $program_increment_creator;
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;
    private LoggerInterface $logger;
    private PendingArtifactCreationStore $pending_artifact_creation_store;
    private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner;
    private SearchTeamsOfProgram $teams_searcher;
    private BuildProject $project_builder;
    private BuildSynchronizedFields $fields_builder;
    private RetrieveFieldValuesGatherer $values_retriever;

    public function __construct(
        RetrievePlanningMilestoneTracker $root_milestone_retriever,
        ProgramIncrementsCreator $program_increment_creator,
        LoggerInterface $logger,
        PendingArtifactCreationStore $pending_artifact_creation_store,
        PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        SearchTeamsOfProgram $teams_searcher,
        BuildProject $project_builder,
        BuildSynchronizedFields $fields_builder,
        RetrieveFieldValuesGatherer $values_retriever
    ) {
        $this->root_milestone_retriever        = $root_milestone_retriever;
        $this->program_increment_creator       = $program_increment_creator;
        $this->logger                          = $logger;
        $this->pending_artifact_creation_store = $pending_artifact_creation_store;
        $this->user_stories_planner            = $user_stories_planner;
        $this->teams_searcher                  = $teams_searcher;
        $this->project_builder                 = $project_builder;
        $this->fields_builder                  = $fields_builder;
        $this->values_retriever                = $values_retriever;
    }

    public function createProgramIncrements(ReplicationData $replication_data): void
    {
        try {
            $this->create($replication_data);
        } catch (TrackerRetrievalException | ProgramIncrementCreationException | FieldRetrievalException | FieldSynchronizationException $exception) {
            $this->logger->error('Error during creation of project increments ', ['exception' => $exception]);
        }
    }

    /**
     * @throws ProgramIncrementCreationException
     * @throws TrackerRetrievalException
     * @throws FieldRetrievalException
     * @throws FieldSynchronizationException
     * @throws TopPlanningNotFoundInProjectException
     */
    private function create(ReplicationData $replication_data): void
    {
        $source_values = SourceTimeboxChangesetValues::fromReplication(
            $this->fields_builder,
            $this->values_retriever,
            $replication_data
        );

        $team_projects = TeamProjectsCollection::fromProgramIdentifier(
            $this->teams_searcher,
            $this->project_builder,
            ProgramIdentifier::fromReplicationData($replication_data)
        );

        $user_identifier            = $replication_data->getUserIdentifier();
        $root_planning_tracker_team = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $this->root_milestone_retriever,
            $team_projects,
            $user_identifier
        );

        $this->program_increment_creator->createProgramIncrements(
            $source_values,
            $root_planning_tracker_team,
            $replication_data->getUserIdentifier()
        );

        $this->pending_artifact_creation_store->deleteArtifactFromPendingCreation(
            $replication_data->getArtifact()->getId(),
            $user_identifier->getId()
        );

        $program_increment_changed = new ProgramIncrementChanged(
            $replication_data->getArtifact()->getId(),
            $replication_data->getTracker()->getTrackerId(),
            $user_identifier
        );

        $this->user_stories_planner->plan($program_increment_changed);
    }
}
