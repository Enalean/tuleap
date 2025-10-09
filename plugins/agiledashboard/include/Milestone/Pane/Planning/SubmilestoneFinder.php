<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Planning;

use PFUser;
use Planning_Milestone;
use PlanningFactory;
use Tracker_HierarchyFactory;
use Tuleap\Tracker\Tracker;

/**
 * I find the suitable submilestone for planning
 */
readonly class SubmilestoneFinder
{
    public function __construct(
        private Tracker_HierarchyFactory $hierarchy_factory,
        private PlanningFactory $planning_factory,
    ) {
    }

    public function findFirstSubmilestoneTracker(PFUser $user, Planning_Milestone $milestone): ?Tracker
    {
        $tracker_id = $milestone->getTrackerId();
        if (is_array($tracker_id)) {
            $tracker_id = array_pop($tracker_id);
        }
        $children = $this->hierarchy_factory->getChildren($tracker_id);

        if (! $children) {
            return null;
        }

        $milestone_backlog_trackers = $milestone->getPlanning()->getBacklogTrackers();
        foreach ($milestone_backlog_trackers as $milestone_backlog_tracker) {
            foreach ($children as $tracker) {
                $planning = $this->planning_factory->getPlanningByPlanningTracker($user, $tracker);

                if (! $planning) {
                    continue;
                }

                $planning_backlog_trackers = $planning->getBacklogTrackers();
                foreach ($planning_backlog_trackers as $planning_backlog_tracker) {
                    if ($milestone_backlog_tracker->getId() === $planning_backlog_tracker->getId()) {
                        return $tracker;
                    }

                    foreach ($this->hierarchy_factory->getAllParents($planning_backlog_tracker) as $backlog_tracker_ancestor) {
                        if ($milestone_backlog_tracker->getId() === $backlog_tracker_ancestor->getId()) {
                            return $tracker;
                        }
                    }
                }
            }
        }

        return null;
    }
}
