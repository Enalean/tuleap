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

namespace Tuleap\ProgramManagement\Adapter\Program\Tracker;

use TrackerFactory;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlannableTrackerCannotBeEmptyException;
use Tuleap\ProgramManagement\Program\Plan\BuildTracker;
use Tuleap\ProgramManagement\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Program\Plan\ProgramIncrementTracker;
use Tuleap\ProgramManagement\Program\Plan\ProgramPlannableTracker;

final class ProgramTrackerAdapter implements BuildTracker
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var PlanStore
     */
    private $plan_store;

    public function __construct(TrackerFactory $tracker_factory, PlanStore $plan_store)
    {
        $this->tracker_factory = $tracker_factory;
        $this->plan_store      = $plan_store;
    }

    /**
     * @throws ProgramTrackerException
     */
    public function buildProgramIncrementTracker(int $tracker_id, int $project_id): ProgramIncrementTracker
    {
        $tracker = $this->getValidTracker($tracker_id, $project_id);

        return new ProgramIncrementTracker($tracker->getId());
    }

    /**
     * @throws ProgramTrackerException
     */
    public function buildPlannableProgramTracker(int $tracker_id, int $project_id): ProgramPlannableTracker
    {
        $tracker = $this->getValidTracker($tracker_id, $project_id);

        if (! $this->plan_store->isPlannable($tracker_id)) {
            throw new ProgramTrackerMustBeDefinedAsPlannableTrackerException($tracker_id);
        }

        return new ProgramPlannableTracker($tracker->getId());
    }


    /**
     * @return array<ProgramPlannableTracker>
     * @throws ProgramTrackerException
     * @throws PlannableTrackerCannotBeEmptyException
     */
    public function buildPlannableTrackerList(array $plannable_trackers_id, int $project_id): array
    {
        $plannable_trackers_ids = [];
        foreach ($plannable_trackers_id as $tracker_id) {
            $tracker = $this->getValidTracker($tracker_id, $project_id);

            $plannable_tracker        = new ProgramPlannableTracker($tracker->getId());
            $plannable_trackers_ids[] = $plannable_tracker;
        }

        if (empty($plannable_trackers_ids)) {
            throw new PlannableTrackerCannotBeEmptyException();
        }

        return $plannable_trackers_ids;
    }

    /**
     * @throws ProgramTrackerException
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
