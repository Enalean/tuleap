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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

class ProgramIncrementArtifactCreatorChecker
{
    /**
     * @var TeamProjectsCollectionBuilder
     */
    private $projects_builder;
    /**
     * @var TrackerCollectionFactory
     */
    private $scale_trackers_factory;
    /**
     * @var SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var CheckSemantic
     */
    private $semantic_checker;

    /**
     * @var CheckRequiredField
     */
    private $required_field_checker;
    /**
     * @var CheckWorkflow
     */
    private $workflow_checker;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TeamProjectsCollectionBuilder $team_projects_collection_builder,
        TrackerCollectionFactory $scale_tracker_factory,
        SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder $field_collection_builder,
        CheckSemantic $semantic_checker,
        CheckRequiredField $required_field_checker,
        CheckWorkflow $workflow_checker,
        LoggerInterface $logger
    ) {
        $this->projects_builder         = $team_projects_collection_builder;
        $this->scale_trackers_factory   = $scale_tracker_factory;
        $this->field_collection_builder = $field_collection_builder;
        $this->semantic_checker         = $semantic_checker;
        $this->required_field_checker   = $required_field_checker;
        $this->workflow_checker         = $workflow_checker;
        $this->logger                   = $logger;
    }

    public function canProgramIncrementBeCreated(ProgramTracker $tracker_data, Project $project_data, PFUser $user): bool
    {
        $program_project = $project_data;
        $this->logger->debug(
            "Checking if program increment can be created in top planning of project " . $program_project->getName() .
            " by user " . $user->getName() . ' (#' . $user->getId() . ')'
        );

        $team_projects_collection = $this->projects_builder->getTeamProjectForAGivenProgramProject(
            $program_project
        );
        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug("No team project found.");
            return true;
        }
        try {
            $program_and_program_increment_trackers = $this->scale_trackers_factory->buildFromProgramProjectAndItsTeam(
                $program_project,
                $team_projects_collection,
                $user
            );
            $program_increment_trackers             = $this->scale_trackers_factory->buildFromTeamProjects(
                $team_projects_collection,
                $user
            );
        } catch (TopPlanningNotFoundInProjectException | PlanningHasNoProgramIncrementException $exception) {
            $this->logger->error("Cannot retrieve all the program increments", ['exception' => $exception]);
            return false;
        }
        if (! $this->semantic_checker->areTrackerSemanticsWellConfigured($tracker_data, $program_increment_trackers)) {
            $this->logger->error("Semantics are not well configured.");

            return false;
        }
        if (! $program_increment_trackers->canUserSubmitAnArtifactInAllTrackers($user)) {
            $this->logger->debug("User cannot submit an artifact in all team trackers.");

            return false;
        }

        try {
            $synchronized_fields_data_collection = $this->field_collection_builder->buildFromSourceTrackers($program_and_program_increment_trackers);
        } catch (FieldSynchronizationException $exception) {
            $this->logger->error("Cannot retrieve all the synchronized fields", ['exception' => $exception]);
            return false;
        }
        if (! $synchronized_fields_data_collection->canUserSubmitAndUpdateAllFields($user)) {
            $this->logger->debug("User cannot submit and update all needed fields in all trackers.");
            return false;
        }

        if (
            ! $this->required_field_checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
                $program_increment_trackers,
                $synchronized_fields_data_collection
            )
        ) {
            $this->logger->debug("A team tracker has a required fields outside the synchronized fields.");
            return false;
        }

        if (
            ! $this->workflow_checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $program_increment_trackers,
                $synchronized_fields_data_collection
            )
        ) {
            $this->logger->debug("A team tracker is using one of the synchronized fields in a workflow rule.");
            return false;
        }

        $this->logger->debug("User can create a project increment in the project.");
        return true;
    }
}
