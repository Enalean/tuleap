<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;

class IterationCreatorChecker
{
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;
    private VerifyIsIterationTracker $verify_is_iteration;
    private RetrieveVisibleIterationTracker $iteration_tracker_retriever;
    private TimeboxCreatorChecker $timebox_creator_checker;
    private LoggerInterface $logger;

    public function __construct(
        RetrievePlanningMilestoneTracker $root_milestone_retriever,
        VerifyIsIterationTracker $verify_is_iteration,
        RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        TimeboxCreatorChecker $timebox_creator_checker,
        LoggerInterface $logger
    ) {
        $this->root_milestone_retriever    = $root_milestone_retriever;
        $this->verify_is_iteration         = $verify_is_iteration;
        $this->iteration_tracker_retriever = $iteration_tracker_retriever;
        $this->timebox_creator_checker     = $timebox_creator_checker;
        $this->logger                      = $logger;
    }

    public function canCreateAnIteration(
        PFUser $user,
        ProgramTracker $tracker,
        ProgramIdentifier $program,
        TeamProjectsCollection $team_projects_collection
    ): bool {
        if (! $this->verify_is_iteration->isIterationTracker($tracker->getTrackerId())) {
            return true;
        }

        $this->logger->debug(
            sprintf(
                'Checking if Iteration can be created in second planning of project #%s by user %s (#%s)',
                $program->getId(),
                $user->getName(),
                $user->getId()
            )
        );

        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug('No team project found.');
            return true;
        }

        try {
            $team_trackers = TrackerCollection::buildSecondPlanningMilestoneTracker(
                $this->root_milestone_retriever,
                $team_projects_collection,
                $user
            );

            $iteration_and_team_trackers = SourceTrackerCollection::fromIterationAndTeamTrackers(
                $this->iteration_tracker_retriever,
                $program,
                $team_trackers,
                $user
            );
        } catch (PlanningNotFoundException | TrackerRetrievalException $exception) {
            $this->logger->error('Cannot retrieve all milestones', ['exception' => $exception]);
            return false;
        }

        if ($iteration_and_team_trackers === null) {
            return true;
        }

        return $this->timebox_creator_checker->canTimeboxBeCreated(
            $tracker,
            $iteration_and_team_trackers,
            $team_trackers,
            $user
        );
    }
}
