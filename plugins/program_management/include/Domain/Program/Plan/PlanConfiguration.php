<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

/**
 * I hold the Plan configuration of a Program.
 * @see NewPlanConfiguration to create a new Plan configuration and save it.
 * @see PlanConfigurationChange to modify a Plan configuration and save it.
 * @psalm-immutable
 */
final readonly class PlanConfiguration
{
    /**
     * @param Option<IterationTrackerIdentifier> $iteration_tracker
     * @param list<int>                          $tracker_ids_that_can_be_planned
     * @param list<int>                          $user_group_ids_that_can_prioritize
     */
    private function __construct(
        public ProgramIdentifier $program_identifier,
        public ProgramIncrementTrackerIdentifier $program_increment_tracker,
        public ProgramIncrementLabels $program_increment_labels,
        public Option $iteration_tracker,
        public IterationLabels $iteration_labels,
        public array $tracker_ids_that_can_be_planned,
        public array $user_group_ids_that_can_prioritize,
    ) {
    }

    /**
     * @param Option<int> $iteration_tracker_id
     * @param list<int>   $tracker_ids_that_can_be_planned
     * @param list<int>   $user_group_ids_that_can_prioritize
     */
    public static function fromRaw(
        ProgramIdentifier $program_identifier,
        int $program_increment_tracker_id,
        ?string $program_increment_label,
        ?string $program_increment_sub_label,
        Option $iteration_tracker_id,
        ?string $iteration_label,
        ?string $iteration_sub_label,
        array $tracker_ids_that_can_be_planned,
        array $user_group_ids_that_can_prioritize,
    ): self {
        return new self(
            $program_identifier,
            ProgramIncrementTrackerIdentifier::fromPlanConfiguration($program_increment_tracker_id),
            ProgramIncrementLabels::fromPlanConfiguration(
                $program_increment_label,
                $program_increment_sub_label
            ),
            $iteration_tracker_id->map(IterationTrackerIdentifier::fromPlanConfiguration(...)),
            IterationLabels::fromPlanConfiguration(
                $iteration_label,
                $iteration_sub_label
            ),
            $tracker_ids_that_can_be_planned,
            $user_group_ids_that_can_prioritize
        );
    }
}
