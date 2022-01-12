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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold the Iteration Tracker identifier and its customized labels, if any.
 * @psalm-immutable
 */
final class IterationTrackerConfiguration
{
    private function __construct(
        public IterationTrackerIdentifier $iteration_tracker,
        public IterationLabels $labels,
    ) {
    }

    public static function fromProgram(
        RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        RetrieveIterationLabels $labels_retriever,
        ProgramIdentifier $program,
        UserIdentifier $user,
    ): ?self {
        $iteration_tracker = IterationTrackerIdentifier::fromProgram($iteration_tracker_retriever, $program, $user);
        if (! $iteration_tracker) {
            return null;
        }
        $labels = IterationLabels::fromIterationTracker($labels_retriever, $iteration_tracker);
        return new self($iteration_tracker, $labels);
    }
}
