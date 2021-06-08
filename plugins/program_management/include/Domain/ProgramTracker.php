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
        Project $project,
        \PFUser $user
    ): self {
        return new self($retriever->retrieveRootPlanningMilestoneTracker($project, $user));
    }

    /**
     * @throws PlanningNotFoundException
     * @throws TrackerRetrievalException
     */
    public static function buildSecondPlanningMilestoneTracker(
        RetrievePlanningMilestoneTracker $retriever,
        Project $project,
        \PFUser $user
    ): self {
        return new self($retriever->retrieveSecondPlanningMilestoneTracker($project, $user));
    }

    /**
     * @throws Program\ProgramTrackerNotFoundException
     * @throws Program\Plan\ProgramHasNoProgramIncrementTrackerException
     */
    public static function buildProgramIncrementTrackerFromProgram(
        RetrieveVisibleProgramIncrementTracker $retriever,
        ProgramIdentifier $program,
        \PFUser $user
    ): self {
        return new self($retriever->retrieveVisibleProgramIncrementTracker($program, $user));
    }

    /**
     * @throws Program\ProgramTrackerNotFoundException
     */
    public static function buildIterationTrackerFromProgram(
        RetrieveVisibleIterationTracker $retriever,
        ProgramIdentifier $program,
        \PFUser $user
    ): ?self {
        $tracker = $retriever->retrieveVisibleIterationTracker($program, $user);

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
    public function getFullTracker(): \Tracker
    {
        return $this->tracker;
    }

    public function userCanSubmitArtifact(\PFUser $user): bool
    {
        return $this->tracker->userCanSubmitArtifact($user);
    }
}
