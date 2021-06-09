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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;

class ProgramIncrementCreatorChecker
{
    private TimeboxCreatorChecker $timebox_creator_checker;
    private VerifyIsProgramIncrementTracker $verify_is_program_increment;
    private RetrievePlanningMilestoneTracker $milestone_retriever;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private LoggerInterface $logger;

    public function __construct(
        TimeboxCreatorChecker $timebox_creator_checker,
        VerifyIsProgramIncrementTracker $verify_is_program_increment,
        RetrievePlanningMilestoneTracker $milestone_retriever,
        RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        LoggerInterface $logger
    ) {
        $this->timebox_creator_checker             = $timebox_creator_checker;
        $this->verify_is_program_increment         = $verify_is_program_increment;
        $this->milestone_retriever                 = $milestone_retriever;
        $this->program_increment_tracker_retriever = $program_increment_tracker_retriever;
        $this->logger                              = $logger;
    }

    public function canCreateAProgramIncrement(
        PFUser $user,
        ProgramTracker $tracker,
        ProgramIdentifier $program,
        TeamProjectsCollection $team_projects_collection
    ): bool {
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

        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug('No team project found.');
            return true;
        }

        try {
            $team_trackers             = TrackerCollection::buildRootPlanningMilestoneTrackers(
                $this->milestone_retriever,
                $team_projects_collection,
                $user
            );
            $program_and_team_trackers = SourceTrackerCollection::fromProgramAndTeamTrackers(
                $this->program_increment_tracker_retriever,
                $program,
                $team_trackers,
                $user
            );
        } catch (PlanningNotFoundException | TrackerRetrievalException | ProgramTrackerNotFoundException $exception) {
            $this->logger->error('Cannot retrieve all milestones', ['exception' => $exception]);
            return false;
        }

        return $this->timebox_creator_checker->canTimeboxBeCreated(
            $tracker,
            $program_and_team_trackers,
            $team_trackers,
            $user
        );
    }
}
