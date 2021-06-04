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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveRootPlanningMilestoneTracker;

class ProgramIncrementCreatorChecker
{
    private TimeboxCreatorChecker $timebox_creator_checker;
    private VerifyIsProgramIncrementTracker $verify_is_program_increment;
    private TeamProjectsCollectionBuilder $team_projects_collection_builder;
    private TrackerCollectionFactory $scale_tracker_factory;
    private RetrieveRootPlanningMilestoneTracker $root_milestone_retriever;
    private LoggerInterface $logger;

    public function __construct(
        TimeboxCreatorChecker $timebox_creator_checker,
        VerifyIsProgramIncrementTracker $verify_is_program_increment,
        TeamProjectsCollectionBuilder $team_projects_collection_builder,
        TrackerCollectionFactory $scale_tracker_factory,
        RetrieveRootPlanningMilestoneTracker $root_milestone_retriever,
        LoggerInterface $logger
    ) {
        $this->timebox_creator_checker          = $timebox_creator_checker;
        $this->verify_is_program_increment      = $verify_is_program_increment;
        $this->team_projects_collection_builder = $team_projects_collection_builder;
        $this->scale_tracker_factory            = $scale_tracker_factory;
        $this->root_milestone_retriever         = $root_milestone_retriever;
        $this->logger                           = $logger;
    }

    public function canCreateAProgramIncrement(PFUser $user, ProgramTracker $tracker, ProgramIdentifier $program): bool
    {
        if (! $this->verify_is_program_increment->isProgramIncrementTracker($tracker->getTrackerId())) {
            return true;
        }

        $this->logger->debug(
            sprintf(
                'Checking if Program Increment can be created in top planning of project #%s by user %s (#%s)',
                $program->getId(),
                $user->getName(),
                $user->getId()
            )
        );

        $team_projects_collection = $this->team_projects_collection_builder->getTeamProjectForAGivenProgramProject(
            $program
        );
        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug("No team project found.");
            return true;
        }
        try {
            $program_and_milestone_trackers = $this->scale_tracker_factory->buildFromProgramProjectAndItsTeam(
                $program,
                $team_projects_collection,
                $user
            );
            $team_trackers                  = TrackerCollection::buildRootPlanningMilestoneTrackers(
                $this->root_milestone_retriever,
                $team_projects_collection,
                $user
            );
        } catch (PlanningNotFoundException | TrackerRetrievalException $exception) {
            $this->logger->error("Cannot retrieve all milestones", ['exception' => $exception]);
            return false;
        }

        return $this->timebox_creator_checker->canTimeboxBeCreated($tracker, $program_and_milestone_trackers, $team_trackers, $user);
    }
}
