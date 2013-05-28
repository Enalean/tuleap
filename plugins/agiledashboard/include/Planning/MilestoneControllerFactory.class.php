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
 * I builds MilestoneController
 */
class Planning_MilestoneControllerFactory {

    /** @var Plugin */
    private $plugin;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    /** @var AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder */
    private $content_presenter_builder;

    public function __construct(
        Plugin $plugin,
        ProjectManager $project_manager,
        Planning_MilestoneFactory $milestone_factory,
        PlanningFactory $planning_factory,
        Tracker_HierarchyFactory $hierarchy_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        Planning_MilestonePaneFactory $pane_factory
    ) {
        $this->plugin                         = $plugin;
        $this->project_manager                = $project_manager;
        $this->milestone_factory              = $milestone_factory;
        $this->planning_factory               = $planning_factory;
        $this->hierarchy_factory              = $hierarchy_factory;
        $this->pane_presenter_builder_factory = $pane_presenter_builder_factory;
        $this->pane_factory                   = $pane_factory;
    }

    /**
     * Builds a new Milestone_Controller instance.
     *
     * @param Codendi_Request $request
     *
     * @return Planning_MilestoneController
     */
    public function getMilestoneController(Codendi_Request $request) {
        return new Planning_MilestoneController(
            $request,
            $this->milestone_factory,
            $this->project_manager,
            $this->pane_factory,
            $this->pane_presenter_builder_factory,
            $this->plugin->getThemePath()
        );
    }
}

?>
