<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Workspace\UserReference;

/**
 * @psalm-immutable
 */
final class PlanChange
{
    /**
    * @param int[]                  $tracker_ids_that_can_be_planned
    * @param non-empty-list<string> $can_possibly_prioritize_ugroups
    */
    private function __construct(
        public PlanProgramIncrementChange $program_increment_change,
        public UserReference $user,
        public int $project_id,
        public array $tracker_ids_that_can_be_planned,
        public array $can_possibly_prioritize_ugroups,
        public ?PlanIterationChange $iteration,
    ) {
    }

    /**
     * @param int[]                  $tracker_ids_that_can_be_planned
     * @param non-empty-list<string> $can_possibly_prioritize_ugroups
     *
     * @throws CannotPlanIntoItselfException
     * @throws ProgramIncrementAndIterationCanNotBeTheSameTrackerException
     */
    public static function fromProgramIncrementAndRaw(
        PlanProgramIncrementChange $program_increment_change,
        UserReference $user,
        int $project_id,
        array $tracker_ids_that_can_be_planned,
        array $can_possibly_prioritize_ugroups,
        ?PlanIterationChange $iteration_representation,
    ): self {
        if (in_array($program_increment_change->tracker_id, $tracker_ids_that_can_be_planned, true)) {
            throw new ProgramIncrementCannotPlanIntoItselfException();
        }

        if ($iteration_representation && $program_increment_change->tracker_id === $iteration_representation->tracker_id) {
            throw new ProgramIncrementAndIterationCanNotBeTheSameTrackerException();
        }

        if ($iteration_representation && in_array($iteration_representation->tracker_id, $tracker_ids_that_can_be_planned, true)) {
            throw new IterationCannotBePlannedException();
        }

        return new self(
            $program_increment_change,
            $user,
            $project_id,
            $tracker_ids_that_can_be_planned,
            $can_possibly_prioritize_ugroups,
            $iteration_representation
        );
    }
}
