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

namespace Tuleap\ProgramManagement\Domain;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramTracker
{
    /**
     * @psalm-readonly
     */
    private \Tracker $tracker;

    public function __construct(\Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanningHasNoProgramIncrementException
     */
    public static function buildMilestoneTrackerFromRootPlanning(
        RetrievePlanningMilestoneTracker $retriever,
        ProgramManagementProject $project,
        UserIdentifier $user_identifier
    ): self {
        return new self($retriever->retrieveRootPlanningMilestoneTracker($project, $user_identifier));
    }

    /**
     * @throws PlanningNotFoundException
     * @throws TrackerRetrievalException
     */
    public static function buildSecondPlanningMilestoneTracker(
        RetrievePlanningMilestoneTracker $retriever,
        ProgramManagementProject $project,
        UserIdentifier $user_identifier
    ): self {
        return new self($retriever->retrieveSecondPlanningMilestoneTracker($project, $user_identifier));
    }

    /**
     * @throws Program\ProgramTrackerNotFoundException
     * @throws Program\Plan\ProgramHasNoProgramIncrementTrackerException
     */
    public static function buildProgramIncrementTrackerFromProgram(
        RetrieveVisibleProgramIncrementTracker $retriever,
        ProgramIdentifier $program,
        UserIdentifier $user_identifier
    ): self {
        return new self($retriever->retrieveVisibleProgramIncrementTracker($program, $user_identifier));
    }

    /**
     * @throws Program\ProgramTrackerNotFoundException
     */
    public static function buildIterationTrackerFromProgram(
        RetrieveVisibleIterationTracker $retriever,
        ProgramIdentifier $program,
        UserIdentifier $user_identifier
    ): ?self {
        $tracker = $retriever->retrieveVisibleIterationTracker($program, $user_identifier);

        if ($tracker === null) {
            return null;
        }

        return new self($tracker);
    }

    /**
     * @psalm-mutation-free
     */
    public function getTrackerId(): int
    {
        return $this->tracker->getId();
    }

    /**
     * @psalm-mutation-free
     */
    public function getTrackerName(): string
    {
        return $this->tracker->getName();
    }

    /**
     * @psalm-mutation-free
     */
    public function getProjectId(): int
    {
        return (int) $this->tracker->getGroupId();
    }

    /**
     * @psalm-mutation-free
     */
    public function getFullTracker(): \Tracker
    {
        return $this->tracker;
    }

    public function userCanSubmitArtifact(RetrieveUser $retrieve_user, UserIdentifier $user_identifier): bool
    {
        $user = $retrieve_user->getUserWithId($user_identifier);
        return $this->tracker->userCanSubmitArtifact($user);
    }
}
