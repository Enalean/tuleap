<?php
/**
 * Copyright Enalean (c) 2013-2015. All rights reserved.
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

class AgileDashboard_Milestone_Pane_TopContent_TopContentPresenterBuilder {

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $strategy_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $collection_factory;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $strategy_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $collection_factory
    ) {
        $this->strategy_factory   = $strategy_factory;
        $this->collection_factory = $collection_factory;
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_Milestone $milestone) {
        $redirect_paremeter   = new Planning_MilestoneRedirectParameter();
        $backlog_strategy     = $this->strategy_factory->getSelfBacklogStrategy($milestone);
        $item_trackers        = $backlog_strategy->getItemTrackers();
        $redirect_to_self     = $redirect_paremeter->getPlanningRedirectToSelf(
            $milestone,
            AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningV2PaneInfo::IDENTIFIER
        );

        return new AgileDashboard_Milestone_Pane_Content_TopContentPresenter(
            $this->collection_factory->getUnassignedOpenCollection($user, $milestone, $backlog_strategy, $redirect_to_self),
            $backlog_strategy->getBacklogItemName(),
            $this->getAddItemsToBacklogUrls($user, $item_trackers, $redirect_to_self),
            $item_trackers,
            $this->canUserPrioritizeBacklog($user, $milestone->getGroupId()),
            $this->getTrackersWithoutInitialEffortSemanticDefined($item_trackers)
        );
    }

    private function getTrackersWithoutInitialEffortSemanticDefined(array $item_trackers) {
        $trackers_without_initial_effort_defined = array();

        foreach ($item_trackers as $item_tracker) {
            if (!AgileDashBoard_Semantic_InitialEffort::load($item_tracker)->getField()) {
                $trackers_without_initial_effort_defined[] = $item_tracker;
            }
        }

        return $trackers_without_initial_effort_defined;
    }

    private function canUserPrioritizeBacklog(PFUser $user, $group_id) {
        $planning_permissions_manager = new PlanningPermissionsManager();
        $root_planning                = PlanningFactory::build()->getRootPlanning($user, $group_id);

        if ($root_planning) {
            return $planning_permissions_manager->userHasPermissionOnPlanning($root_planning->getId(), $root_planning->getGroupId(), $user, PlanningPermissionsManager::PERM_PRIORITY_CHANGE);
        }

        return false;
    }

    private function getAddItemsToBacklogUrls(PFUser $user, array $item_trackers, $redirect_to_self) {
        $submit_urls = array();

        foreach ($item_trackers as $item_tracker) {
            if ($item_tracker->userCanSubmitArtifact($user)) {
                $submit_urls[] = array(
                    'tracker_type' => $item_tracker->getName(),
                    'tracker_id'   => $item_tracker->getId(),
                    'submit_url'   => $item_tracker->getSubmitUrl().'&'.$redirect_to_self
                );
            }
        }

        return $submit_urls;
    }
}
?>
