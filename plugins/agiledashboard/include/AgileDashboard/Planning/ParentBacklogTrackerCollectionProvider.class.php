<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * I return a collection of backlog tracker, parent of a given tracker,
 * until I matches a planning for teh given milestone
 *
 * Example of planning configuration:
 *
 *                  Theme
 * Release  --------- Epic
 *    Sprint  --------- Story
 *                        Task
 *                          TestCase
 *
 * For the milestone "release 6.2" and the backlog tracker "task", the collection
 * will be ["epic", "story", "task"]
 */
class AgileDashboard_Planning_ParentBacklogTrackerCollectionProvider {

    /** @return Tracker[] */
    public function getParentBacklogTrackerCollection(Tracker $backlog_tracker, Planning_Milestone $milestone) {
        $trackers                  = array($backlog_tracker);
        $milestone_backlog_tracker = $milestone->getPlanning()->getBacklogTracker();
        $current_backlog_tracker   = $backlog_tracker;
        if ($current_backlog_tracker == $milestone_backlog_tracker) {
            return array($milestone_backlog_tracker);
        }

        while (($parent = $current_backlog_tracker->getParent()) && $parent != $milestone_backlog_tracker) {
            $trackers[] = $parent;
            $current_backlog_tracker = $parent;
        }
        if (! $parent) {
            return array();
        }
        if ($parent == $milestone_backlog_tracker) {
            $trackers[] = $parent;
        }

        return array_reverse($trackers);
    }
}
