<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Tracker;

/**
 * I return the nearest planning tracker for the given tracker.
 *
 * Example of planning configuration:
 *
 * Release  --------- Epic
 *    Sprint  --------- Story
 *                        Task
 *
 * => The nearest planning tracker of task is sprint.
 * => The nearest planning tracker of sprint is null.
 */
class AgileDashboard_Planning_NearestPlanningTrackerProvider
{
    public function __construct(private readonly PlanningFactory $planning_factory)
    {
    }

    public function getNearestPlanningTracker(PFUser $user, Tracker $backlog_tracker, Tracker_HierarchyFactory $hierarchy_factory): ?Tracker
    {
        $current_backlog_tracker  = $backlog_tracker;
        $nearest_planning_tracker = null;
        while ($current_backlog_tracker && $nearest_planning_tracker === null) {
            $plannings = $this->getPlanningTrackersInRightOrder($user, $current_backlog_tracker, $hierarchy_factory);
            if (! $plannings) {
                $current_backlog_tracker = $current_backlog_tracker->getParent();
                continue;
            }
            $nearest_planning         = array_shift($plannings);
            $nearest_planning_tracker = $nearest_planning->getPlanningTracker();
        }
        return $nearest_planning_tracker;
    }

    private function getPlanningTrackersInRightOrder(PFUser $user, Tracker $backlog_tracker, Tracker_HierarchyFactory $hierarchy_factory): array
    {
        $planning_trackers = $this->planning_factory->getPlanningsByBacklogTracker($user, $backlog_tracker);

        $trackers_ids = $this->getAllPlanningTrackersIds($planning_trackers);
        $trackers_ids = $this->sortPlanningTrackersIdsUsingHierarchy($trackers_ids, $hierarchy_factory);

        return $this->sortPlanningTrackersUsingAReference($trackers_ids, $planning_trackers);
    }

    private function getAllPlanningTrackersIds(array $planning_trackers): array
    {
        $trackers_ids = [];

        foreach ($planning_trackers as $planning_tracker) {
            $trackers_ids[] = $planning_tracker->getPlanningTrackerId();
        }

        return $trackers_ids;
    }

    private function sortPlanningTrackersIdsUsingHierarchy(array $trackers_ids, Tracker_HierarchyFactory $hierarchy_factory): array
    {
        $hierarchy    = $hierarchy_factory->getHierarchy($trackers_ids);
        $trackers_ids = $hierarchy->sortTrackerIds($trackers_ids);

        return $trackers_ids;
    }

    private function sortPlanningTrackersUsingAReference(array $reference, array $planning_trackers): array
    {
        $ordered_plannings = [];

        foreach ($planning_trackers as $planning_tracker) {
            $ordered_plannings[array_search($planning_tracker->getPlanningTrackerId(), $reference)] = $planning_tracker;
        }

        krsort($ordered_plannings);
        return $ordered_plannings;
    }
}
