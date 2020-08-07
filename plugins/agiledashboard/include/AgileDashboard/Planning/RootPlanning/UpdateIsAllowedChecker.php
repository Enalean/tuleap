<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use Tuleap\Tracker\Report\TrackerNotFoundException;

class UpdateIsAllowedChecker
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;
    /**
     * @var BacklogTrackerRemovalChecker
     */
    private $backlog_tracker_removal_checker;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        \PlanningFactory $planning_factory,
        BacklogTrackerRemovalChecker $backlog_tracker_removal_checker,
        \TrackerFactory $tracker_factory
    ) {
        $this->planning_factory                = $planning_factory;
        $this->backlog_tracker_removal_checker = $backlog_tracker_removal_checker;
        $this->tracker_factory                 = $tracker_factory;
    }

    /**
     * @throws \Tuleap\AgileDashboard\Planning\TrackerHaveAtLeastOneAddToTopBacklogPostActionException
     * @throws TrackerNotFoundException
     */
    public function checkUpdateIsAllowed(
        \Planning $planning,
        \PlanningParameters $updated_planning,
        \PFUser $user
    ): void {
        $root_planning = $this->planning_factory->getRootPlanning(
            $user,
            (int) $planning->getGroupId()
        );

        if (! $root_planning) {
            return;
        }

        if (! $this->isPlanningTheRootPlanning($planning, $root_planning)) {
            return;
        }

        $this->backlog_tracker_removal_checker->checkRemovedBacklogTrackersCanBeRemoved($planning, $updated_planning);
        $this->checkMilestoneTrackerIdIsStillAValidTracker($updated_planning);
    }

    private function isPlanningTheRootPlanning(\Planning $planning, \Planning $root_planning): bool
    {
        return (int) $planning->getId() === (int) $root_planning->getId();
    }

    /**
     * @throws TrackerNotFoundException
     */
    private function checkMilestoneTrackerIdIsStillAValidTracker(\PlanningParameters $updated_planning): void
    {
        if (! $this->tracker_factory->getTrackerById((int) $updated_planning->planning_tracker_id)) {
            throw new TrackerNotFoundException();
        }
    }
}
