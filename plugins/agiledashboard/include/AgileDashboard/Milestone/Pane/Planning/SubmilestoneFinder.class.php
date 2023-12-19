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


/**
 * I find the suitable submilestone for planning
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder
{
    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    public function __construct(
        Tracker_HierarchyFactory $hierarchy_factory,
        PlanningFactory $planning_factory,
    ) {
        $this->hierarchy_factory = $hierarchy_factory;
        $this->planning_factory  = $planning_factory;
    }

    public function findFirstSubmilestoneTracker(Planning_Milestone $milestone)
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
                $planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);

                if (! $planning) {
                    continue;
                }

                $planning_backlog_trackers = $planning->getBacklogTrackers();
                foreach ($planning_backlog_trackers as $planning_backlog_tracker) {
                    if ((int) $milestone_backlog_tracker->getId() === (int) $planning_backlog_tracker->getId()) {
                        return $tracker;
                    }

                    foreach ($this->hierarchy_factory->getAllParents($planning_backlog_tracker) as $backlog_tracker_ancestor) {
                        if ((int) $milestone_backlog_tracker->getId() === (int) $backlog_tracker_ancestor->getId()) {
                            return $tracker;
                        }
                    }
                }
            }
        }
    }
}
