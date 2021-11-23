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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

final class ProgramIncrementCreatorChecker
{
    public function __construct(
        private TimeboxCreatorChecker $timebox_creator_checker,
        private VerifyIsProgramIncrementTracker $verify_is_program_increment,
        private RetrieveMirroredProgramIncrementTracker $milestone_retriever,
        private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        private LogMessage $logger
    ) {
    }

    public function canCreateAProgramIncrement(
        TrackerReference $tracker,
        ProgramIdentifier $program,
        TeamProjectsCollection $team_projects_collection,
        ConfigurationErrorsCollector $errors_collector,
        UserReference $user_reference
    ): bool {
        if (! $this->verify_is_program_increment->isProgramIncrementTracker($tracker->getId())) {
            return true;
        }

        $this->logger->debug(
            sprintf(
                'Checking if Program Increment can be created in top planning of program #%s by user %s (#%s)',
                $program->getId(),
                $user_reference->getName(),
                $user_reference->getId()
            )
        );

        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug('No team project found.');
            return true;
        }

        try {
            $team_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
                $this->milestone_retriever,
                $team_projects_collection,
                $user_reference,
                $errors_collector
            );
        } catch (TrackerRetrievalException $exception) {
            $this->logger->error('Planning configuration is incorrect, it does not have a tracker', ['exception' => $exception]);
            return false;
        }
        if ($team_trackers->isEmpty()) {
            $this->logger->error('Cannot retrieve root planning milestone tracker of all teams');
            return false;
        }

        try {
            $program_and_team_trackers = SourceTrackerCollection::fromProgramAndTeamTrackers(
                $this->program_increment_tracker_retriever,
                $program,
                $team_trackers,
                $user_reference
            );
        } catch (ProgramTrackerNotFoundException $exception) {
            $this->logger->error('Cannot retrieve all milestones', ['exception' => $exception]);
            return false;
        }

        $can_timebox_be_created =  $this->timebox_creator_checker->canTimeboxBeCreated(
            $tracker,
            $program_and_team_trackers,
            $team_trackers,
            $user_reference,
            $errors_collector
        );

        if (! $can_timebox_be_created) {
            $this->logger->error(
                sprintf(
                    'Program increment cannot be created in program #%s by user %s (#%s)',
                    $program->getId(),
                    $user_reference->getName(),
                    $user_reference->getId()
                )
            );
        }

        return $can_timebox_be_created;
    }
}
