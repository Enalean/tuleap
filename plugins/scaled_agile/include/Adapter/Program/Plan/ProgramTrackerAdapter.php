<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program\Plan;

use TrackerFactory;
use Tuleap\ScaledAgile\Program\Plan\BuildTracker;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;
use Tuleap\ScaledAgile\Program\Plan\ProgramPlannableTracker;

final class ProgramTrackerAdapter implements BuildTracker
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws PlanTrackerDoesNotBelongToProjectException
     * @throws PlanTrackerNotFoundException
     */
    public function buildProgramIncrementTracker(int $tracker_id, int $project_id): ProgramIncrementTracker
    {
        $tracker = $this->getValidTracker($tracker_id, $project_id);

        return new ProgramIncrementTracker($tracker->getId());
    }

    /**
     * @throws PlanTrackerDoesNotBelongToProjectException
     * @throws PlanTrackerNotFoundException
     * @throws PlannableTrackerCannotBeEmptyException
     *
     * @return array<ProgramPlannableTracker>
     */
    public function buildPlannableTrackers(array $plannable_trackers_id, int $project_id): array
    {
        $plannable_trackers_ids = [];
        foreach ($plannable_trackers_id as $tracker_id) {
            $tracker = $this->getValidTracker($tracker_id, $project_id);

            $plannable_tracker = new ProgramPlannableTracker($tracker->getId());
            $plannable_trackers_ids[]  = $plannable_tracker;
        }

        if (empty($plannable_trackers_ids)) {
            throw new PlannableTrackerCannotBeEmptyException();
        }

        return $plannable_trackers_ids;
    }

    /**
     * @throws PlanTrackerDoesNotBelongToProjectException
     * @throws PlanTrackerNotFoundException
     */
    private function getValidTracker(int $tracker_id, int $project_id): \Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new PlanTrackerNotFoundException($tracker_id);
        }

        if ((int) $tracker->getGroupId() !== $project_id) {
            throw new PlanTrackerDoesNotBelongToProjectException($tracker_id, $project_id);
        }

        return $tracker;
    }
}
