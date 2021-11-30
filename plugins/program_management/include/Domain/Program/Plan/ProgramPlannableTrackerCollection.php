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
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTracker;

/**
 * @psalm-immutable
 */
final class ProgramPlannableTrackerCollection
{
    /**
     * @var ProgramPlannableTracker[]
     */
    public array $trackers;

    /**
     * @param ProgramPlannableTracker[] $trackers
     */
    private function __construct(array $trackers)
    {
        $this->trackers = $trackers;
    }

    /**
     * @param int[] $plannable_trackers_id
     * @throws PlannableTrackerCannotBeEmptyException
     * @throws ProgramTrackerException
     */
    public static function fromIds(
        RetrieveTracker $tracker_retriever,
        array $plannable_trackers_id,
        ProgramForAdministrationIdentifier $program,
    ): self {
        $trackers = [];
        foreach ($plannable_trackers_id as $tracker_id) {
            $trackers[] = ProgramPlannableTracker::build($tracker_retriever, $tracker_id, $program);
        }

        if (empty($trackers)) {
            throw new PlannableTrackerCannotBeEmptyException();
        }

        return new self($trackers);
    }
}
