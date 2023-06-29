<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Kanban\KanbanFactory;

class AgileDashboard_HierarchyChecker
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var KanbanFactory
     */
    private $kanban_factory;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        PlanningFactory $planning_factory,
        KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory,
    ) {
        $this->planning_factory = $planning_factory;
        $this->kanban_factory   = $kanban_factory;
        $this->tracker_factory  = $tracker_factory;
    }

    private function isScrumHierarchy(Tracker $tracker)
    {
        $project   = $tracker->getProject();
        $hierarchy = $tracker->getHierarchy();

        return $this->checkHierarchyContainsGivenTrackerIds($hierarchy, $this->getScrumTrackerIds($project));
    }

    private function isKanbanHierarchy(Tracker $tracker)
    {
        $project   = $tracker->getProject();
        $hierarchy = $tracker->getHierarchy();

        return $this->checkHierarchyContainsGivenTrackerIds($hierarchy, $this->getKanbanTrackerIds($project));
    }

    public function isPartOfScrumOrKanbanHierarchy(Tracker $tracker)
    {
        return ($this->isScrumHierarchy($tracker) || $this->isKanbanHierarchy($tracker));
    }

    private function getScrumTrackerIds(Project $project)
    {
        $planning_tracker_ids = $this->planning_factory->getPlanningTrackerIdsByGroupId($project->getID());
        $backlog_tracker_ids  = $this->planning_factory->getBacklogTrackerIdsByGroupId($project->getID());

        return array_unique(array_merge($planning_tracker_ids, $backlog_tracker_ids));
    }

    /**
     * @return int[]
     */
    private function getKanbanTrackerIds(Project $project): array
    {
        return $this->kanban_factory->getKanbanTrackerIds((int) $project->getID());
    }

    private function checkHierarchyContainsGivenTrackerIds(Tracker_Hierarchy $hierarchy, array $tracker_ids)
    {
        foreach ($hierarchy->flatten() as $tracker_id) {
            if (in_array($tracker_id, $tracker_ids)) {
                return true;
            }
        }

        return false;
    }

    public function getADTrackerIdsByProjectId($project_id)
    {
        $planning_tracker_ids       = $this->planning_factory->getPlanningTrackerIdsByGroupId($project_id);
        $backlog_tracker_ids        = $this->planning_factory->getBacklogTrackerIdsByGroupId($project_id);
        $kanban_tracker_ids         = $this->kanban_factory->getKanbanTrackerIds($project_id);
        $agiledashboard_tracker_ids = array_unique(
            array_merge($planning_tracker_ids, $backlog_tracker_ids, $kanban_tracker_ids)
        );
        $hierachy_factory           = $this->tracker_factory->getHierarchyFactory();
        $trackers_hierarchy         = $hierachy_factory->getHierarchy($agiledashboard_tracker_ids);
        $hierarchy_tracker_ids      = $trackers_hierarchy->flatten();

        return array_unique(array_merge($agiledashboard_tracker_ids, $hierarchy_tracker_ids));
    }
}
