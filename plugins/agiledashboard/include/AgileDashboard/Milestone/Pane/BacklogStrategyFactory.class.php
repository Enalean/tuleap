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
 * I build AgileDashboard_Milestone_Pane_BacklogStrategy
 */
class AgileDashboard_Milestone_Pane_BacklogStrategyFactory {

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
     * @return AgileDashboard_Milestone_Pane_BacklogStrategy
     */
    public function getBacklogStrategy(Planning_ArtifactMilestone $milestone) {
        $milestone_backlog_artifacts = $this->getBacklogArtifacts($milestone);
        $backlog_tracker_children    = $milestone->getPlanning()->getPlanningTracker()->getChildren();
        $backlog_tracker             = $milestone->getPlanning()->getBacklogTracker();

        if ($backlog_tracker_children) {
            $first_child_tracker  = current($backlog_tracker_children);
            $first_child_planning = $this->planning_factory->getPlanningByPlanningTracker($first_child_tracker);
            if ($first_child_planning) {
                $first_child_backlog_tracker = $first_child_planning->getBacklogTracker();

                if ($first_child_backlog_tracker != $backlog_tracker) {
                    return new AgileDashboard_Milestone_Pane_DescendantBacklogStrategy(
                        $milestone_backlog_artifacts,
                        $first_child_backlog_tracker->getName(),
                        $this->dao
                    );
                }
            }
        }

        return new AgileDashboard_Milestone_Pane_SelfBacklogStrategy(
            $milestone_backlog_artifacts,
            $backlog_tracker->getName()
        );
    }

    private function getBacklogArtifacts(Planning_ArtifactMilestone $milestone) {
        return $this->dao->getBacklogArtifacts($milestone->getArtifactId())->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
    }
}
?>
