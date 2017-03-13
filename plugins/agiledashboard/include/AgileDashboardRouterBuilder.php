<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentationFactory;
use Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;

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

        $planning_factory                                = $this->getPlanningFactory();
        $milestone_factory                               = $this->getMilestoneFactory();
        $hierarchy_factory                               = $this->getHierarchyFactory();
        $mono_milestone_checker                          = $this->getMonoMileStoneChecker();
        $submilestone_finder = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            $hierarchy_factory,
            $planning_factory,
            $mono_milestone_checker,
            $this->getTrackerFactory()
        );
        $milestone_representation_builder                = $this->getMilestoneRepresentationBuilder();
        $paginated_backlog_items_representations_builder = $this->getPaginatedBacklogItemsRepresentationsBuilder();

        $pane_info_factory = new AgileDashboard_PaneInfoFactory(
            $request->getCurrentUser(),
            $submilestone_finder,
            $plugin->getThemePath()
        );

        $pane_presenter_builder_factory = $this->getPanePresenterBuilderFactory($milestone_factory);

        $pane_factory = $this->getPaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $submilestone_finder,
            $pane_info_factory,
            $plugin,
            $milestone_representation_builder,
            $paginated_backlog_items_representations_builder
        );
        $top_milestone_pane_factory = $this->getTopMilestonePaneFactory(
            $request,
            $pane_presenter_builder_factory,
            $plugin,
            $milestone_representation_builder,
            $paginated_backlog_items_representations_builder
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

        $mono_milestone_checker = new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory);

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
            $this->getHierarchyChecker(),
            $mono_milestone_checker,
            new ScrumPlanningFilter($this->getHierarchyChecker(), $mono_milestone_checker, $planning_factory)
        );
    }

    /** @return Planning_MilestonePaneFactory */
    private function getPaneFactory(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        AgileDashboard_PaneInfoFactory $pane_info_factory,
        Plugin $plugin,
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $milestone_representation_builder,
        AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder $paginated_backlog_items_representations_builder
    ) {
        return new Planning_MilestonePaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $submilestone_finder,
            $pane_info_factory,
            $milestone_representation_builder,
            $paginated_backlog_items_representations_builder
        );
    }

    /**
     * @return Planning_VirtualTopMilestonePaneFactory
     */
    private function getTopMilestonePaneFactory(
        $request,
        $pane_presenter_builder_factory,
        Plugin $plugin,
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $milestone_representation_builder,
        AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder $paginated_backlog_items_representations_builder
    ) {
        return new Planning_VirtualTopMilestonePaneFactory(
            $request,
            $pane_presenter_builder_factory,
            $plugin->getThemePath(),
            $milestone_representation_builder,
            $paginated_backlog_items_representations_builder
        );
    }

    private function getPanePresenterBuilderFactory($milestone_factory) {
        return new AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory(
            $this->getBacklogStrategyFactory(),
            $this->getBacklogItemPresenterCollectionFactory($milestone_factory)
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
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            $this->getMonoMileStoneChecker()
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

    private function getMilestoneRepresentationBuilder() {
        return new AgileDashboard_Milestone_MilestoneRepresentationBuilder(
            $this->getMilestoneFactory(),
            $this->getBacklogStrategyFactory(),
            EventManager::instance(),
            $this->getMonoMileStoneChecker()
        );
    }

    private function getPaginatedBacklogItemsRepresentationsBuilder() {
        return new AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder(
            new BacklogItemRepresentationFactory(),
            $this->getBacklogItemCollectionFactory(),
            $this->getBacklogStrategyFactory()
        );
    }

    private function getBacklogItemCollectionFactory() {
        return new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            $this->getMilestoneFactory(),
            $this->getPlanningFactory(),
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );
    }

    private function getBacklogStrategyFactory() {
        return new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            $this->getPlanningFactory(),
            $this->getMonoMileStoneChecker()
        );
    }

    private function getMonoMileStoneChecker()
    {
        return new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $this->getPlanningFactory());
    }

}
