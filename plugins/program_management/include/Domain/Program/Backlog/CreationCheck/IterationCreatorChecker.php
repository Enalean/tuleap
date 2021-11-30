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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

final class IterationCreatorChecker
{
    public function __construct(
        private RetrieveMirroredIterationTracker $milestone_retriever,
        private VerifyIsIterationTracker $verify_is_iteration,
        private RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        private TimeboxCreatorChecker $timebox_creator_checker,
        private LogMessage $logger,
    ) {
    }

    public function canCreateAnIteration(
        TrackerReference $tracker,
        ProgramIdentifier $program,
        TeamProjectsCollection $team_projects_collection,
        ConfigurationErrorsCollector $errors_collector,
        UserReference $user_identifier,
    ): bool {
        if (! $this->verify_is_iteration->isIterationTracker($tracker->getId())) {
            return true;
        }

        $this->logger->debug(
            sprintf(
                'Checking if Iteration can be created in second planning of program #%s by user %s (#%s)',
                $program->getId(),
                $user_identifier->getName(),
                $user_identifier->getId()
            )
        );

        if ($team_projects_collection->isEmpty()) {
            $this->logger->debug('No team project found.');
            return true;
        }

        try {
            $team_trackers = TrackerCollection::buildSecondPlanningMilestoneTracker(
                $this->milestone_retriever,
                $team_projects_collection,
                $user_identifier,
                $errors_collector
            );

            $iteration_and_team_trackers = SourceTrackerCollection::fromIterationAndTeamTrackers(
                $this->iteration_tracker_retriever,
                $program,
                $team_trackers,
                $user_identifier
            );
        } catch (TrackerRetrievalException $exception) {
            $this->logger->error('Cannot retrieve all milestones', ['exception' => $exception]);
            return false;
        }

        if ($iteration_and_team_trackers === null) {
            return true;
        }

        $can_timebox_be_created = $this->timebox_creator_checker->canTimeboxBeCreated(
            $tracker,
            $iteration_and_team_trackers,
            $team_trackers,
            $user_identifier,
            $errors_collector
        );

        if (! $can_timebox_be_created) {
            $this->logger->error(
                sprintf(
                    'Iteration cannot be created in program #%s by user %s (#%s)',
                    $program->getId(),
                    $user_identifier->getName(),
                    $user_identifier->getId()
                )
            );
        }

        return $can_timebox_be_created;
    }
}
