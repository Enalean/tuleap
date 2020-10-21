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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use PFUser;
use Planning_VirtualTopMilestone;
use Psr\Log\LoggerInterface;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;

class ProjectIncrementArtifactCreatorChecker
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
     * @var SynchronizedFieldCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var SemanticChecker
     */
    private $semantic_checker;

    /**
     * @var RequiredFieldChecker
     */
    private $required_field_checker;
    /**
     * @var WorkflowChecker
     */
    private $workflow_checker;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TeamProjectsCollectionBuilder $team_projects_collection_builder,
        TrackerCollectionFactory $scale_tracker_factory,
        SynchronizedFieldCollectionBuilder $field_collection_builder,
        SemanticChecker $semantic_checker,
        RequiredFieldChecker $required_field_checker,
        WorkflowChecker $workflow_checker,
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

    public function canProjectIncrementBeCreated(Planning_VirtualTopMilestone $top_milestone, PFUser $user): bool
    {
        $this->logger->debug(
            "Checking if program increment can be created in top planning of project " . $top_milestone->getProject(
            )->getUnixName() .
            " by user " . $user->getName() . ' (#' . $user->getId() . ')'
        );

        $program_project = $top_milestone->getProject();

        $team_projects_collection = $this->projects_builder->getTeamProjectForAGivenProgramProject(
            $program_project
        );
        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug("No team project found.");
            return true;
        }
        try {
            $program_and_project_increment_trackers = $this->scale_trackers_factory->buildFromProgramProjectAndItsTeam(
                $program_project,
                $team_projects_collection,
                $user
            );
            $project_increment_trackers             = $this->scale_trackers_factory->buildFromTeamProjects(
                $team_projects_collection,
                $user
            );
        } catch (ProjectIncrementTrackerRetrievalException $exception) {
            $this->logger->error("Cannot retrieve all the program increments", ['exception' => $exception]);

            return false;
        }
        if (! $this->semantic_checker->areTrackerSemanticsWellConfigured($top_milestone, $program_and_project_increment_trackers)) {
            $this->logger->error("Semantics are not well configured.");

            return false;
        }
        if (! $project_increment_trackers->canUserSubmitAnArtifactInAllTrackers($user)) {
            $this->logger->debug("User cannot submit an artifact in all team trackers.");

            return false;
        }

        try {
            $fields = $this->field_collection_builder->buildFromSourceTrackers($program_and_project_increment_trackers);
        } catch (SynchronizedFieldRetrievalException $exception) {
            $this->logger->error("Cannot retrieve all the synchronized fields", ['exception' => $exception]);
            return false;
        }
        if (! $fields->canUserSubmitAndUpdateAllFields($user)) {
            $this->logger->debug("User cannot submit and update all needed fields in all trackers.");
            return false;
        }

        if (
            ! $this->required_field_checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
                $project_increment_trackers,
                $fields
            )
        ) {
            $this->logger->debug("A team tracker has a required fields outside the synchronized fields.");
            return false;
        }

        if (
            ! $this->workflow_checker->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $project_increment_trackers,
                $fields
            )
        ) {
            $this->logger->debug("A team tracker is using one of the synchronized fields in a workflow rule.");
            return false;
        }

        $this->logger->debug("User can create a project increment in the project.");
        return true;
    }
}
