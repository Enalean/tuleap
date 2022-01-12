<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerConfiguration;

/**
 * @psalm-immutable
 */
final class ProgramBacklogConfiguration
{
    private function __construct(
        public bool $can_create_program,
        public bool $has_plan_permissions,
        public int $program_increment_tracker_id,
        public ?string $program_increment_label,
        public ?string $program_increment_sublabel,
        public bool $is_configured,
        public bool $is_iteration_tracker_defined,
        public ?string $iteration_label,
    ) {
    }

    public static function buildForPotentialProgram(): self
    {
        return new self(false, false, 0, '', '', false, false, '');
    }

    public static function fromProgramIncrementAndIterationConfiguration(
        ProgramIncrementTrackerConfiguration $increment_configuration,
        ?IterationTrackerConfiguration $iteration_configuration,
    ): self {
        return new self(
            $increment_configuration->canCreateProgramIncrement(),
            $increment_configuration->hasPlanPermissions(),
            $increment_configuration->getProgramIncrementTrackerId(),
            $increment_configuration->getProgramIncrementLabel(),
            $increment_configuration->getProgramIncrementSubLabel(),
            true,
            $iteration_configuration !== null,
            $iteration_configuration->labels->label ?? ''
        );
    }
}
