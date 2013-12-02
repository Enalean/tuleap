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
 * Like RepRap, I build builders
 */
class AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory {

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $strategy_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $row_collection_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterFactory */
    private $submilestone_presenter_factory;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $strategy_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $row_collection_factory,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterFactory $submilestone_presenter_factory
    ) {
        $this->strategy_factory               = $strategy_factory;
        $this->row_collection_factory         = $row_collection_factory;
        $this->milestone_factory              = $milestone_factory;
        $this->submilestone_presenter_factory = $submilestone_presenter_factory;
    }

    /**
     * @return AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder
     */
    public function getContentPresenterBuilder() {
        return new AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder(
            $this->strategy_factory,
            $this->row_collection_factory
        );
    }

    /**
     * @return AgileDashboard_Milestone_Pane_TopContent_TopContentPresenterBuilder
     */
    public function getTopContentPresenterBuilder() {
        return new AgileDashboard_Milestone_Pane_TopContent_TopContentPresenterBuilder(
            $this->strategy_factory,
            $this->row_collection_factory
        );
    }

    /**
     * @return AgileDashboard_Milestone_Pane_Planning_PlanningPresenterBuilder
     */
    public function getPlanningPresenterBuilder() {
        return new AgileDashboard_Milestone_Pane_Planning_PlanningPresenterBuilder(
            $this->strategy_factory,
            $this->row_collection_factory,
            $this->milestone_factory,
            $this->submilestone_presenter_factory
        );
    }

    /**
     * @return AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningPresenterBuilder
     */
    public function getTopPlanningPresenterBuilder() {
        return new AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningPresenterBuilder(
            $this->strategy_factory,
            $this->row_collection_factory,
            $this->milestone_factory,
            $this->submilestone_presenter_factory
        );
    }

    /**
     * @return AgileDashboard_SubmilestonePresenterBuilder
     */
    public function getSubmilestonePresenterBuilder() {
        return new AgileDashboard_SubmilestonePresenterBuilder(
            $this->row_collection_factory,
            $this->strategy_factory
        );
    }
}
?>
