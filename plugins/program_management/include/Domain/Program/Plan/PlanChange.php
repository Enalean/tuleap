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

/**
 * @psalm-immutable
 */
final class PlanChange
{
    /**
     * @var PlanProgramIncrementChange
     */
    public $program_increment_change;
    /**
     * @var \PFUser
     */
    public $user;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var array
     */
    public $tracker_ids_that_can_be_planned;
    /**
     * @var array
     */
    public $can_possibly_prioritize_ugroups;
    /**
     * @var ?PlanIterationChange
     */
    public $iteration;

    private function __construct(
        PlanProgramIncrementChange $program_increment_change,
        \PFUser $user,
        int $project_id,
        array $tracker_ids_that_can_be_planned,
        array $can_possibly_prioritize_ugroups,
        ?PlanIterationChange $iteration
    ) {
        $this->program_increment_change        = $program_increment_change;
        $this->user                            = $user;
        $this->project_id                      = $project_id;
        $this->tracker_ids_that_can_be_planned = $tracker_ids_that_can_be_planned;
        $this->can_possibly_prioritize_ugroups = $can_possibly_prioritize_ugroups;
        $this->iteration                       = $iteration;
    }

    /**
     * @param int[]                  $tracker_ids_that_can_be_planned
     * @param non-empty-list<string> $can_possibly_prioritize_ugroups
     * @throws CannotPlanIntoItselfException
     */
    public static function fromProgramIncrementAndRaw(
        PlanProgramIncrementChange $program_increment_change,
        \PFUser $user,
        int $project_id,
        array $tracker_ids_that_can_be_planned,
        array $can_possibly_prioritize_ugroups,
        ?PlanIterationChange $iteration_representation
    ): self {
        if (in_array($program_increment_change->tracker_id, $tracker_ids_that_can_be_planned, true)) {
            throw new ProgramIncrementCannotPlanIntoItselfException();
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
