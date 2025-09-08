<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTrackerFromId;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\CheckNewIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\CheckNewPlannableTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\CheckNewProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;

final class TrackerConfigurationChecker implements CheckNewIterationTracker, CheckNewProgramIncrementTracker, CheckNewPlannableTracker
{
    public function __construct(
        private RetrieveFullTrackerFromId $tracker_retriever,
    ) {
    }

    #[\Override]
    public function checkIterationTrackerIsValid(
        int $iteration_tracker_id,
        ProgramForAdministrationIdentifier $program,
    ): void {
        $this->checkTrackerIsValid($iteration_tracker_id, $program);
    }

    #[\Override]
    public function checkProgramIncrementTrackerIsValid(
        int $program_increment_tracker_id,
        ProgramForAdministrationIdentifier $program,
    ): void {
        $this->checkTrackerIsValid($program_increment_tracker_id, $program);
    }

    #[\Override]
    public function checkPlannableTrackerIsValid(
        int $plannable_tracker_id,
        ProgramForAdministrationIdentifier $program,
    ): void {
        $this->checkTrackerIsValid($plannable_tracker_id, $program);
    }

    /**
     * @throws PlanTrackerDoesNotBelongToProjectException
     * @throws PlanTrackerNotFoundException
     */
    private function checkTrackerIsValid(int $tracker_id, ProgramForAdministrationIdentifier $program): void
    {
        $tracker = $this->tracker_retriever->getTrackerFromId($tracker_id);
        if (! $tracker) {
            throw new PlanTrackerNotFoundException($tracker_id);
        }
        if ((int) $tracker->getGroupId() !== $program->id) {
            throw new PlanTrackerDoesNotBelongToProjectException($tracker_id, $program->id);
        }
    }
}
