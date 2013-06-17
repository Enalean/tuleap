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

class AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningPresenterBuilder {

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory */
    private $collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $strategy_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var bool */
    private $can_plan = true;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $strategy_factory,
        AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory $collection_factory,
        Planning_MilestoneFactory $milestone_factory
    ) {
        $this->strategy_factory   = $strategy_factory;
        $this->collection_factory = $collection_factory;
        $this->milestone_factory  = $milestone_factory;
    }

    public function getMilestoneTopPlanningPresenter(PFUser $user, Planning_Milestone $milestone, Tracker $milestone_tracker) {
        $redirect_paremeter     = new Planning_MilestoneRedirectParameter();
        $backlog_strategy       = $this->strategy_factory->getSelfBacklogStrategy($milestone);
        $redirect_to_self       = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningPaneInfo::IDENTIFIER);

        $backlog_collection = $this->collection_factory->getUnplannedOpenCollection(
            $user,
            $milestone,
            $backlog_strategy,
            $redirect_to_self
        );

        $milestone_collection = $this->getMilestoneCollection($user, $milestone, $milestone_tracker, $redirect_to_self);

        return new AgileDashboard_Milestone_Pane_Planning_PlanningPresenter(
            $backlog_collection,
            $milestone_collection,
            $milestone,
            $backlog_collection->getParentItemName(),
            $backlog_strategy->getItemTracker()->getName(),
            $milestone_collection->getName(),
            $milestone_collection->getSubmitNewUrlLinkedToTracker($milestone_tracker),
            $milestone_collection->canCreateNew($user),
            $this->can_plan,
            $redirect_to_self
        );
    }

    private function getMilestoneCollection(PFUser $user, Planning_Milestone $milestone, Tracker $submilestone_tracker, $redirect_to_self) {
        $milestone_collection = new AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterCollection($submilestone_tracker);
        $milestones = array_reverse($this->milestone_factory->getSubMilestones($user, $milestone));

        foreach ($milestones as $milestone) {
            $this->milestone_factory->updateMilestoneContextualInfo($user, $milestone);
            $presenter = new AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenter($milestone, $redirect_to_self, $user);

            $milestone_collection->push($presenter);

            if (! $this->canPlan($user, $milestone)) {
                $this->can_plan = false;
            }
        }
        
        return $milestone_collection;
    }

    /**
     * @return bool
     */
    private function canPlan(PFUser $user, Planning_Milestone $milestone) {
        $art_link_field = $milestone->getArtifact()->getAnArtifactLinkField($user);
        if ($art_link_field && $art_link_field->userCanUpdate($user)) {
            return true;
        }
        return false;
    }
}
?>
