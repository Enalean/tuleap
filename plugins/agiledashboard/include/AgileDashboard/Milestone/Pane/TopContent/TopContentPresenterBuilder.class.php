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

class AgileDashboard_Milestone_Pane_TopContent_TopContentPresenterBuilder {

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $strategy_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory */
    private $collection_factory;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $strategy_factory,
        AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory $collection_factory
    ) {
        $this->strategy_factory   = $strategy_factory;
        $this->collection_factory = $collection_factory;
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_Milestone $milestone) {
        $redirect_paremeter   = new Planning_MilestoneRedirectParameter();
        $backlog_strategy     = $this->strategy_factory->getSelfBacklogStrategy($milestone);
        $item_trackers        = $backlog_strategy->getItemTrackers();
        $identifier           = AgileDashboard_Milestone_Pane_TopContent_TopContentPaneInfo::IDENTIFIER;
        $redirect_to_self     = $redirect_paremeter->getPlanningRedirectToSelf($milestone, $identifier);
        $new_backlog_item_url = $this->getAddItemsToBacklogUrls($user, $item_trackers, $redirect_to_self);

        $todo_collection = $this->collection_factory->getUnassignedOpenCollection($user, $milestone, $backlog_strategy, $redirect_to_self);
        $done_collection = new AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection();
        $trackers_without_initial_effort_semantic_defined = $this->getTrackersWithoutInitialEffortSemanticDefined($item_trackers);

        $content_presenter = new AgileDashboard_Milestone_Pane_Content_ContentPresenterDescendant(
            $todo_collection,
            $done_collection,
            new AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection(),
            $backlog_strategy->getBacklogItemName(),
            $new_backlog_item_url,
            $item_trackers,
            $this->canUserPrioritizeBacklog($user, $item_trackers),
            $trackers_without_initial_effort_semantic_defined
        );

        return $content_presenter;
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

    private function canUserPrioritizeBacklog(PFUser $user, array $item_trackers) {
        $can_prioritize = true;

        foreach ($item_trackers as $item_tracker) {
            $can_prioritize = $can_prioritize && $item_tracker->userCanSubmitArtifact($user);
        }

        return $can_prioritize;
    }

    private function getAddItemsToBacklogUrls(PFUser $user, array $item_trackers, $redirect_to_self) {
        $submit_urls = array();

        foreach ($item_trackers as $item_tracker) {
            if ($item_tracker->userCanSubmitArtifact($user)) {
                $submit_urls[] = array(
                    'tracker_type' => $item_tracker->getName(),
                    'submit_url'   => $item_tracker->getSubmitUrl().'&'.$redirect_to_self
                );
            }
        }

        return $submit_urls;
    }
}
?>
