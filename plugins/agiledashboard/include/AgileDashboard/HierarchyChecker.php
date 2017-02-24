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
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $kanban_factory;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        PlanningFactory $planning_factory,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory
    ) {
        $this->planning_factory  = $planning_factory;
        $this->kanban_factory    = $kanban_factory;
        $this->tracker_factory   = $tracker_factory;
    }

    public function isScrumHierarchy(Tracker $tracker) {
        $project   = $tracker->getProject();
        $hierarchy = $tracker->getHierarchy();

        return $this->checkHierarchyContainsGivenTrackerIds($hierarchy, $this->getScrumTrackerIds($project));
    }

    public function isKanbanHierarchy(Tracker $tracker) {
        $project   = $tracker->getProject();
        $hierarchy = $tracker->getHierarchy();

        return $this->checkHierarchyContainsGivenTrackerIds($hierarchy, $this->getKanbanTrackerIds($project));
    }

    public function isPartOfScrumOrKanbanHierarchy(Tracker $tracker) {
        return ($this->isScrumHierarchy($tracker) || $this->isKanbanHierarchy($tracker));
    }

    public function getDeniedTrackersForATrackerHierarchy(Tracker $tracker, PFUser $user) {
        $concerned_by_scrum  = $this->isScrumHierarchy($tracker);
        $concerned_by_kanban = $this->isKanbanHierarchy($tracker);

        if (! $concerned_by_scrum && ! $concerned_by_kanban) {
            return array();
        }

        $available_trackers = $this->tracker_factory->getTrackersByGroupIdUserCanView($tracker->getGroupId(), $user);

        if ($concerned_by_scrum) {
            $possible_trackers = $this->getTrackersConcernedByScrum($available_trackers, $tracker->getProject());

            return array_diff($available_trackers, $possible_trackers);
        }

        $possible_trackers = $this->getTrackersConcernedByKanban($available_trackers, $tracker->getProject());
        return array_diff($available_trackers, $possible_trackers);
    }

    private function getScrumTrackerIds(Project $project) {
        $planning_tracker_ids = $this->planning_factory->getPlanningTrackerIdsByGroupId($project->getID());
        $backlog_tracker_ids  = $this->planning_factory->getBacklogTrackerIdsByGroupId($project->getID());

        return array_unique(array_merge($planning_tracker_ids, $backlog_tracker_ids));
    }

    private function getKanbanTrackerIds(Project $project) {
        return $this->kanban_factory->getKanbanTrackerIds($project->getID());
    }

    private function checkHierarchyContainsGivenTrackerIds(Tracker_Hierarchy $hierarchy, array $tracker_ids) {
        foreach ($hierarchy->flatten() as $tracker_id) {

            if (in_array($tracker_id, $tracker_ids)) {
                return true;
            }
        }

        return false;
    }

    private function getTrackersConcernedByKanban(array $available_trackers, Project $project) {
        $scrum_tracker_ids = $this->getScrumTrackerIds($project);

        return $this->getTrackerListPurgedFromUneligibleTrackersAndTheirHierarchy($available_trackers, $scrum_tracker_ids);
    }

    private function getTrackersConcernedByScrum(array $available_trackers, Project $project) {
        $kanban_tracker_ids = $this->getKanbanTrackerIds($project);

        return $this->getTrackerListPurgedFromUneligibleTrackersAndTheirHierarchy($available_trackers, $kanban_tracker_ids);
    }

    private function getTrackerListPurgedFromUneligibleTrackersAndTheirHierarchy(array $available_trackers, $uneligible_trackers) {
        $possible_trackers = $available_trackers;

        foreach ($available_trackers as $tracker) {

            if (! isset($possible_trackers[$tracker->getId()])) {
                continue;
            }

            $hierarchy = $tracker->getHierarchy();

            if ($this->checkHierarchyContainsGivenTrackerIds($hierarchy, $uneligible_trackers)) {
                $this->removeAllConcernedValues($possible_trackers, $hierarchy);
            }
        }

        return $possible_trackers;
    }

    private function removeAllConcernedValues(array &$possible_trackers, Tracker_Hierarchy $hierarchy) {
        foreach($hierarchy->flatten() as $tracker_id) {
            unset($possible_trackers[$tracker_id]);
        }
    }

    public function getADTrackerIdsByProjectId($project_id) {
        $planning_tracker_ids = $this->planning_factory->getPlanningTrackerIdsByGroupId($project_id);
        $backlog_tracker_ids  = $this->planning_factory->getBacklogTrackerIdsByGroupId($project_id);
        $kanban_tracker_ids = $this->kanban_factory->getKanbanTrackerIds($project_id);
        $agiledashboard_tracker_ids = array_unique(
            array_merge($planning_tracker_ids, $backlog_tracker_ids, $kanban_tracker_ids)
        );
        $hierachy_factory = $this->tracker_factory->getHierarchyFactory();
        $trackers_hierarchy = $hierachy_factory->getHierarchy($agiledashboard_tracker_ids);
        $hierarchy_tracker_ids = $trackers_hierarchy->flatten();

        return array_unique(array_merge($agiledashboard_tracker_ids, $hierarchy_tracker_ids));
    }
}
