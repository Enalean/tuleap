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

class AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder {

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
        $backlog_strategy     = $this->strategy_factory->getBacklogStrategy($milestone);
        $redirect_to_self     = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_Content_ContentPaneInfo::IDENTIFIER);

        return $backlog_strategy->getPresenter(
            $user,
            $milestone,
            $this->collection_factory->getTodoCollection($user, $milestone, $backlog_strategy, $redirect_to_self),
            $this->collection_factory->getDoneCollection($user, $milestone, $backlog_strategy, $redirect_to_self),
            $this->collection_factory->getInconsistentCollection($user, $milestone, $backlog_strategy, $redirect_to_self),
            $redirect_to_self
        );
    }
}
?>
