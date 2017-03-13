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
use Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker;

/**
 * I build AgileDashboard_Milestone_Backlog_BacklogStrategy
 */
class AgileDashboard_Milestone_Backlog_BacklogStrategyFactory {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        PlanningFactory $planning_factory,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker
    ) {
        $this->dao                          = $dao;
        $this->artifact_factory             = $artifact_factory;
        $this->planning_factory             = $planning_factory;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
    }

    /**
     * @return AgileDashboard_Milestone_Backlog_BacklogStrategy
     */
    public function getBacklogStrategy(Planning_Milestone $milestone, $limit = null, $offset = null) {
        $backlog_trackers_children_can_manage = array();
        $first_child_backlog_trackers = $this->getFirstChildBacklogTracker($milestone);
        if ($first_child_backlog_trackers) {
            $backlog_trackers_children_can_manage = array_merge($backlog_trackers_children_can_manage, $first_child_backlog_trackers);
        } else {
            $backlog_trackers_children_can_manage = array_merge($backlog_trackers_children_can_manage, $milestone->getPlanning()->getBacklogTrackers());
        }
        return $this->getDescendantBacklogStrategy($milestone, $backlog_trackers_children_can_manage, $limit, $offset);
    }

    public function getSelfBacklogStrategy(Planning_Milestone $milestone, $limit = null, $offset = null) {
        return $this->getDescendantBacklogStrategy(
            $milestone, $milestone->getPlanning()->getBacklogTrackers(),
            $limit,
            $offset
        );
    }

    private function getDescendantBacklogStrategy(
        Planning_Milestone $milestone,
        array $backlog_trackers_children_can_manage,
        $limit = null,
        $offset = null
    ) {
        return new AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy(
            $this->artifact_factory,
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $backlog_trackers_children_can_manage,
            $this->dao,
            $this->scrum_mono_milestone_checker,
            $limit,
            $offset
        );
    }

    private function getFirstChildBacklogTracker(Planning_Milestone $milestone) {
        $backlog_tracker_children  = $milestone->getPlanning()->getPlanningTracker()->getChildren();
        if ($backlog_tracker_children) {
            $first_child_tracker  = current($backlog_tracker_children);
            $first_child_planning = $this->planning_factory->getPlanningByPlanningTracker($first_child_tracker);
            if ($first_child_planning) {
                return $first_child_planning->getBacklogTrackers();
            }
        }
        return null;
    }
}