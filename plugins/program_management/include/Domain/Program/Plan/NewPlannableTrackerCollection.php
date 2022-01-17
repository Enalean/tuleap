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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;

/**
 * I hold a collection of NewPlannableTracker
 * @see NewPlannableTracker
 * @psalm-immutable
 */
final class NewPlannableTrackerCollection
{
    /**
     * @param NewPlannableTracker[] $trackers
     */
    private function __construct(public array $trackers)
    {
    }

    /**
     * @param int[] $plannable_trackers_ids
     * @throws PlannableTrackerCannotBeEmptyException
     * @throws ProgramTrackerException
     */
    public static function fromIds(
        CheckNewPlannableTracker $tracker_checker,
        array $plannable_trackers_ids,
        ProgramForAdministrationIdentifier $program,
    ): self {
        if (empty($plannable_trackers_ids)) {
            throw new PlannableTrackerCannotBeEmptyException();
        }
        return new self(
            array_map(
                static fn($tracker_id) => NewPlannableTracker::fromId($tracker_checker, $tracker_id, $program),
                $plannable_trackers_ids
            )
        );
    }
}
