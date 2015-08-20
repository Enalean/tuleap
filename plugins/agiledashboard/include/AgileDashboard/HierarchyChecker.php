<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class AgileDashboard_HierarchyChecker {

    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $kanban_factory;

    public function __construct(
        Tracker_HierarchyFactory $hierarchy_factory,
        PlanningFactory $planning_factory,
        AgileDashboard_KanbanFactory $kanban_factory
    ) {
        $this->hierarchy_factory = $hierarchy_factory;
        $this->planning_factory  = $planning_factory;
        $this->kanban_factory    = $kanban_factory;
    }

    public function isScrumHierarchy(Tracker $tracker) {
        $project   = $tracker->getProject();
        $hierarchy = $this->hierarchy_factory->getHierarchy(array($tracker->getId()));

        $planning_tracker_ids = $this->planning_factory->getPlanningTrackerIdsByGroupId($project->getID());
        $backlog_tracker_ids  = $this->planning_factory->getBacklogTrackerIdsByGroupId($project->getID());

        $scrum_tracker_ids = array_unique(array_merge($planning_tracker_ids, $backlog_tracker_ids));

        foreach ($hierarchy->flatten() as $tracker_id) {

            if (in_array($tracker_id, $scrum_tracker_ids)) {
                return true;
            }
        }

        return false;
    }

    public function isKanbanHierarchy(Tracker $tracker) {
        $project   = $tracker->getProject();
        $hierarchy = $this->hierarchy_factory->getHierarchy(array($tracker->getId()));

        $kanban_tracker_ids = $this->kanban_factory->getKanbanTrackerIds($project->getID());

        foreach ($hierarchy->flatten() as $tracker_id) {

            if (in_array($tracker_id, $kanban_tracker_ids)) {
                return true;
            }
        }

        return false;
    }
}