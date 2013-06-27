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
 * I build AgileDashboard_Milestone_Backlog_BacklogStrategy
 */
class AgileDashboard_Milestone_Backlog_BacklogStrategyFactory {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        PlanningFactory $planning_factory
    ) {
        $this->dao              = $dao;
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
    }

    /**
     * @return AgileDashboard_Milestone_Backlog_BacklogStrategy
     */
    public function getBacklogStrategy(Planning_Milestone $milestone) {
        $self_strategy = $this->getSelfBacklogStrategy($milestone);
        $first_child_backlog_tracker = $this->getFirstChildBacklogTracker($milestone);
        if ($first_child_backlog_tracker && ($first_child_backlog_tracker != $milestone->getPlanning()->getBacklogTracker())) {
            return $this->getDescendantBacklogStrategy($self_strategy, $milestone, $first_child_backlog_tracker);
        }
        return $self_strategy;
    }

    public function getSelfBacklogStrategy(Planning_Milestone $milestone) {
        return new AgileDashboard_Milestone_Backlog_SelfBacklogStrategy(
            $this->getBacklogArtifacts($milestone),
            $milestone->getPlanning()->getBacklogTracker()
        );
    }

    private function getDescendantBacklogStrategy(AgileDashboard_Milestone_Backlog_SelfBacklogStrategy $self_strategy, Planning_Milestone $milestone, Tracker $first_child_backlog_tracker) {
        return new AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy(
            $this->getBacklogArtifacts($milestone),
            $milestone->getPlanning()->getBacklogTracker(),
            $first_child_backlog_tracker,
            $this->dao,
            $self_strategy
        );
    }

    private function getFirstChildBacklogTracker(Planning_Milestone $milestone) {
        $backlog_tracker_children  = $milestone->getPlanning()->getPlanningTracker()->getChildren();
        if ($backlog_tracker_children) {
            $first_child_tracker  = current($backlog_tracker_children);
            $first_child_planning = $this->planning_factory->getPlanningByPlanningTracker($first_child_tracker);
            if ($first_child_planning) {
                return $first_child_planning->getBacklogTracker();
            }
        }
        return null;
    }

    private function getBacklogArtifacts(Planning_Milestone $milestone) {
        if ($milestone instanceof Planning_VirtualTopMilestone) {
            return $this->dao
                ->getTopBacklogArtifacts($milestone->getPlanning()->getBacklogTrackerId())
                ->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
        }

        return $this->dao
                ->getBacklogArtifacts($milestone->getArtifactId())
                ->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
    }
}
?>
