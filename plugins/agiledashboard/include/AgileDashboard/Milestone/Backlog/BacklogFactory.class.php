<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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
 * I build AgileDashboard_Milestone_Backlog_Backlog
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class AgileDashboard_Milestone_Backlog_BacklogFactory
{
    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        PlanningFactory $planning_factory,
    ) {
        $this->dao              = $dao;
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
    }

    /**
     * @return AgileDashboard_Milestone_Backlog_Backlog
     */
    public function getBacklog(Planning_Milestone $milestone, $limit = null, $offset = null)
    {
        $backlog_trackers_children_can_manage = [];
        $first_child_backlog_trackers         = $this->getFirstChildBacklogTracker($milestone);
        if ($first_child_backlog_trackers) {
            $backlog_trackers_children_can_manage = array_merge($backlog_trackers_children_can_manage, $first_child_backlog_trackers);
        } else {
            $backlog_trackers_children_can_manage = array_merge($backlog_trackers_children_can_manage, $milestone->getPlanning()->getBacklogTrackers());
        }
        return $this->instantiateBacklog($milestone, $backlog_trackers_children_can_manage, $limit, $offset);
    }

    public function getSelfBacklog(Planning_Milestone $milestone, $limit = null, $offset = null): AgileDashboard_Milestone_Backlog_Backlog
    {
        return $this->instantiateBacklog(
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $limit,
            $offset
        );
    }

    private function instantiateBacklog(
        Planning_Milestone $milestone,
        array $backlog_trackers_children_can_manage,
        $limit = null,
        $offset = null,
    ): AgileDashboard_Milestone_Backlog_Backlog {
        return new AgileDashboard_Milestone_Backlog_Backlog(
            $this->artifact_factory,
            $milestone,
            $milestone->getPlanning()->getBacklogTrackers(),
            $backlog_trackers_children_can_manage,
            $this->dao,
            $limit,
            $offset
        );
    }

    private function getFirstChildBacklogTracker(Planning_Milestone $milestone)
    {
        $backlog_tracker_children = $milestone->getPlanning()->getPlanningTracker()->getChildren();
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
