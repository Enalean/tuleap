<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboardRouterBuilder {

    /** PluginFactory */
    private $plugin_factory;

    public function __construct() {
        $this->plugin_factory = PluginFactory::instance();
    }

    /**
     * @param Codendi_Request $request
     *
     * @return AgileDashboardRouter
     */
    public function build(Codendi_Request $request) {
        $plugin = $this->plugin_factory->getPluginByName(
            AgileDashboardPlugin::PLUGIN_NAME
        );

        $planning_factory    = $this->getPlanningFactory();
        $milestone_factory   = $this->getMilestoneFactory();
        $hierarchy_factory   = $this->getHierarchyFactory();
        $submilestone_finder = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder($hierarchy_factory, $planning_factory);

        $pane_info_factory = new AgileDashboard_PaneInfoFactory(
            $request->getCurrentUser(),
            $submilestone_finder,
            $plugin->getThemePath()
        );

        $pane_presenter_builder_factory = $this->getPanePresenterBuilderFactory($milestone_factory, $pane_info_factory);

        $pane_factory = $this->getPaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $submilestone_finder,
            $pane_info_factory,
            $plugin
        );
        $top_milestone_pane_factory = $this->getTopMilestonePaneFactory(
            $request,
            $pane_presenter_builder_factory,
            $plugin
        );

        $milestone_controller_factory = new Planning_MilestoneControllerFactory(
            $plugin,
            ProjectManager::instance(),
            $milestone_factory,
            $this->getPlanningFactory(),
            $hierarchy_factory,
            $pane_presenter_builder_factory,
            $pane_factory,
            $top_milestone_pane_factory
        );

        return new AgileDashboardRouter(
            $plugin,
            $milestone_factory,
            $planning_factory,
            new Planning_ShortAccessFactory($planning_factory, $pane_info_factory),
            $milestone_controller_factory,
            ProjectManager::instance(),
            new AgileDashboard_XMLFullStructureExporter(
                EventManager::instance(),
                $this
            ),
            $this->getKanbanManager(),
            $this->getConfigurationManager(),
            $this->getKanbanFactory(),
            new PlanningPermissionsManager(),
            $this->getHierarchyChecker()
        );
    }

    /** @return Planning_MilestonePaneFactory */
    private function getPaneFactory(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        AgileDashboard_PaneInfoFactory $pane_info_factory,
        Plugin $plugin
    ) {
        return new Planning_MilestonePaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $submilestone_finder,
            $pane_info_factory,
            $plugin->getThemePath()
        );
    }

    /**
     * @return Planning_VirtualTopMilestonePaneFactory
     */
    private function getTopMilestonePaneFactory(
        $request,
        $pane_presenter_builder_factory,
        Plugin $plugin
    ) {
        return new Planning_VirtualTopMilestonePaneFactory(
            $request,
            $pane_presenter_builder_factory,
            $plugin->getThemePath()
        );
    }

    private function getPanePresenterBuilderFactory($milestone_factory, $pane_info_factory) {
        $icon_factory = new AgileDashboard_PaneIconLinkPresenterCollectionFactory($pane_info_factory);

        return new AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory(
            $this->getBacklogStrategyFactory(),
            $this->getBacklogItemPresenterCollectionFactory($milestone_factory),
            $milestone_factory,
            new AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterFactory($milestone_factory, $icon_factory)
        );
    }

    /**
     * Builds a new PlanningFactory instance.
     *
     * @return PlanningFactory
     */
    private function getPlanningFactory() {
        return PlanningFactory::build();
    }

    /**
     * Builds a new Planning_MilestoneFactory instance.
     * @return Planning_MilestoneFactory
     */
    private function getMilestoneFactory() {
        return new Planning_MilestoneFactory(
            $this->getPlanningFactory(),
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            $this->getTrackerFactory(),
            $this->getStatusCounter(),
            new PlanningPermissionsManager()
        );
    }

    private function getBacklogItemPresenterCollectionFactory($milestone_factory) {
        return new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            $milestone_factory,
            $this->getPlanningFactory(),
            new AgileDashboard_Milestone_Backlog_BacklogItemPresenterBuilder()
        );
    }

    private function getHierarchyFactory() {
        return Tracker_HierarchyFactory::instance();
    }

    /**
     * @return AgileDashboard_KanbanManager
     */
    private function getKanbanManager() {
        return new AgileDashboard_KanbanManager(
            new AgileDashboard_KanbanDao(),
            $this->getTrackerFactory(),
            $this->getHierarchyChecker()
        );
    }

    /**
     * @return AgileDashboard_HierarchyChecker
     */
    private function getHierarchyChecker() {
        return new AgileDashboard_HierarchyChecker(
            $this->getPlanningFactory(),
            $this->getKanbanFactory(),
            $this->getTrackerFactory()
        );
    }

    /**
     * @return TrackerFactory
     */
    private function getTrackerFactory() {
        return TrackerFactory::instance();
    }

    /**
     * @return AgileDashboard_ConfigurationManager
     */
    private function getConfigurationManager() {
        return new AgileDashboard_ConfigurationManager(
            new AgileDashboard_ConfigurationDao()
        );
    }

    /**
     * @return AgileDashboard_KanbanFactory
     */
    private function getKanbanFactory() {
        return new AgileDashboard_KanbanFactory(
            $this->getTrackerFactory(),
            new AgileDashboard_KanbanDao()
        );
    }

    private function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }

    private function getStatusCounter() {
        return new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $this->getArtifactFactory()
        );
    }

    private function getBacklogStrategyFactory() {
        return new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            PlanningFactory::build()
        );
    }
}
