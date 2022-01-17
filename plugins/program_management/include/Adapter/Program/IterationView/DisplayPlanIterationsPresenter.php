<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\IterationView;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlannedIterations;

/**
 * @psalm-immutable
 */
final class DisplayPlanIterationsPresenter
{
    private function __construct(
        public string $program_flags,
        public string $program_privacy,
        public string $program,
        public string $program_increment,
        public string $iterations_labels,
        public bool $is_user_admin,
        public bool $is_accessibility_mode_enabled,
        public int $iteration_tracker_id,
    ) {
    }

    public static function fromPlannedIterations(PlannedIterations $planned_iterations): self
    {
        return new self(
            json_encode($planned_iterations->getProgramFlag(), JSON_THROW_ON_ERROR),
            json_encode($planned_iterations->getProgramPrivacy(), JSON_THROW_ON_ERROR),
            json_encode($planned_iterations->getProgramBaseInfo(), JSON_THROW_ON_ERROR),
            json_encode($planned_iterations->getProgramIncrementInfo(), JSON_THROW_ON_ERROR),
            json_encode(
                IterationLabelsPresenter::fromLabels($planned_iterations->getIterationLabels()),
                JSON_THROW_ON_ERROR
            ),
            $planned_iterations->isUserAdmin(),
            $planned_iterations->isAccessibilityModeEnabled(),
            $planned_iterations->getIterationTrackerId()
        );
    }
}
