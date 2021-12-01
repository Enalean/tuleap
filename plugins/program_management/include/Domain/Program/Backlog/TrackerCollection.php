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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\PlanningHasNoMilestoneTrackerException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanSubmit;

/**
 * I contain all the Teams' Mirrored Timebox trackers.
 */
final class TrackerCollection
{
    /**
     * @param TrackerReference[] $mirrored_timebox_trackers
     */
    private function __construct(private array $mirrored_timebox_trackers)
    {
    }

    /**
     * @throws PlanningHasNoMilestoneTrackerException
     */
    public static function buildRootPlanningMilestoneTrackers(
        RetrieveMirroredProgramIncrementTracker $retriever,
        TeamProjectsCollection $teams,
        UserIdentifier $user_identifier,
        ConfigurationErrorsCollector $errors_collector,
    ): self {
        $trackers = [];
        foreach ($teams->getTeamProjects() as $team) {
            $milestone_tracker = $retriever->retrieveRootPlanningMilestoneTracker(
                $team,
                $user_identifier,
                $errors_collector
            );
            if ($milestone_tracker !== null) {
                $trackers[] = $milestone_tracker;
            }
        }

        return new self($trackers);
    }

    /**
     * @throws PlanningHasNoMilestoneTrackerException
     */
    public static function buildSecondPlanningMilestoneTracker(
        RetrieveMirroredIterationTracker $retriever,
        TeamProjectsCollection $teams,
        UserIdentifier $user_identifier,
        ConfigurationErrorsCollector $errors_collector,
    ): self {
        $trackers = [];
        foreach ($teams->getTeamProjects() as $team) {
            $team_tracker = $retriever->retrieveSecondPlanningMilestoneTracker($team, $user_identifier, $errors_collector);
            if ($team_tracker) {
                $trackers[] = $team_tracker;
            }
        }

        return new self($trackers);
    }

    /**
     * @return int[]
     * @psalm-mutation-free
     */
    public function getTrackerIds(): array
    {
        return array_map(
            static fn(TrackerReference $tracker) => $tracker->getId(),
            $this->mirrored_timebox_trackers
        );
    }

    /**
     * @return TrackerReference[]
     * @psalm-mutation-free
     */
    public function getTrackers(): array
    {
        return $this->mirrored_timebox_trackers;
    }

    public function isEmpty(): bool
    {
        return empty($this->mirrored_timebox_trackers);
    }

    public function canUserSubmitAnArtifactInAllTrackers(
        UserIdentifier $user_identifier,
        ConfigurationErrorsCollector $configuration_errors,
        VerifyUserCanSubmit $user_can_submit_in_tracker_verifier,
    ): bool {
        $can_submit = true;
        foreach ($this->mirrored_timebox_trackers as $milestone_tracker) {
            if (! $user_can_submit_in_tracker_verifier->canUserSubmitArtifact($user_identifier, $milestone_tracker)) {
                $configuration_errors->userCanNotSubmitInTeam($milestone_tracker);
                $can_submit = false;
                if (! $configuration_errors->shouldCollectAllIssues()) {
                    return $can_submit;
                }
            }
        }

        return $can_submit;
    }
}
