<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

/**
 * I find the suitable submilestone for planning
 */
class AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder {

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    public function __construct(Tracker_HierarchyFactory $hierarchy_factory, PlanningFactory $planning_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
        $this->planning_factory  = $planning_factory;
    }

    public function findSubmilestone(Planning_Milestone $milestone) {
        $children = $this->hierarchy_factory->getChildren($milestone->getTrackerId());

        if (! $children) {
            return null;
        }

        $milestone_backlog_tracker = $milestone->getPlanning()->getBacklogTracker();

        foreach ($children as $tracker) {
            $planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);

            if (! $planning) {
                continue;
            }

            $planning_backlog_tracker  = $planning->getBacklogTracker();
            if ($milestone_backlog_tracker == $planning_backlog_tracker) {
                return $tracker;
            }

            $backlog_tracker_ancestors = $this->hierarchy_factory->getAllParents($planning_backlog_tracker);
            if (in_array($milestone_backlog_tracker, $backlog_tracker_ancestors)) {
                return $tracker;
            }
        }
    }
}
?>
