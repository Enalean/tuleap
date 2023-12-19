<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\AgileDashboard\Milestone\Backlog\RecentlyVisitedTopBacklogDao;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarDao;
use Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever;
use Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardPermissionsRepresentationBuilder;
use Tuleap\AgileDashboard\PermissionsPerGroup\PlanningPermissionsRepresentationBuilder;
use Tuleap\AgileDashboard\Planning\BacklogTrackersUpdateChecker;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\AgileDashboard\Planning\MilestoneControllerFactory;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Planning\RootPlanning\BacklogTrackerRemovalChecker;
use Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;
use Tuleap\User\ProvideCurrentUser;

class AgileDashboardRouterBuilder // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * @var Planning_MilestonePaneFactory
     */
    private $pane_factory;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var AllBreadCrumbsForMilestoneBuilder
     */
    private $all_bread_crumbs_for_milestone_builder;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $backlog_factory;
    private ProvideCurrentUser $current_user_provider;

    public function __construct(
        Planning_MilestonePaneFactory $pane_factory,
        VisitRecorder $visit_recorder,
        AllBreadCrumbsForMilestoneBuilder $all_bread_crumbs_for_milestone_builder,
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        ProvideCurrentUser $current_user_provider,
    ) {
        $this->pane_factory                           = $pane_factory;
        $this->visit_recorder                         = $visit_recorder;
        $this->all_bread_crumbs_for_milestone_builder = $all_bread_crumbs_for_milestone_builder;
        $this->backlog_factory                        = $backlog_factory;
        $this->current_user_provider                  = $current_user_provider;
    }

    public function build(Codendi_Request $request): AgileDashboardRouter
    {
        $project_explicit_backlog_dao = new ExplicitBacklogDao();
        $tracker_dao                  = new TrackerDao();
        $planning_dao                 = new PlanningDao($tracker_dao);
        $planning_permissions_manager = new PlanningPermissionsManager();
        $tracker_factory              = TrackerFactory::instance();
        $planning_factory             = new PlanningFactory(
            $planning_dao,
            $tracker_factory,
            $planning_permissions_manager
        );
        $milestone_factory            = $this->getMilestoneFactory();

        $event_manager = EventManager::instance();

        $tracker_new_dropdown_link_presenter_builder = new TrackerNewDropdownLinkPresenterBuilder();

        $service_crumb_builder   = new AgileDashboardCrumbBuilder();
        $admin_crumb_builder     = new AdministrationCrumbBuilder();
        $header_options_inserter = new CurrentContextSectionToHeaderOptionsInserter();

        $milestone_controller_factory = new MilestoneControllerFactory(
            ProjectManager::instance(),
            $milestone_factory,
            $this->pane_factory,
            new \Tuleap\AgileDashboard\Planning\VirtualTopMilestonePresenterBuilder(
                $event_manager,
                $project_explicit_backlog_dao,
            ),
            $service_crumb_builder,
            $this->visit_recorder,
            $this->all_bread_crumbs_for_milestone_builder,
            new HeaderOptionsProvider(
                $this->backlog_factory,
                new AgileDashboard_PaneInfoIdentifier(),
                $tracker_new_dropdown_link_presenter_builder,
                new HeaderOptionsForPlanningProvider(
                    new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
                        Tracker_HierarchyFactory::instance(),
                        $planning_factory,
                    ),
                    $tracker_new_dropdown_link_presenter_builder,
                    $header_options_inserter,
                ),
                new \Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever(
                    $planning_factory,
                ),
                $header_options_inserter
            ),
            new \Tuleap\AgileDashboard\CSRFSynchronizerTokenProvider(),
            new RecentlyVisitedTopBacklogDao(),
        );

        $ugroup_manager = new UGroupManager();

        $db_connection         = DBFactory::getMainTuleapDBConnection();
        $scrum_planning_filter = new ScrumPlanningFilter($planning_factory);
        $form_element_factory  = Tracker_FormElementFactory::instance();

        return new AgileDashboardRouter(
            $milestone_factory,
            $planning_factory,
            $milestone_controller_factory,
            ProjectManager::instance(),
            new AgileDashboard_XMLFullStructureExporter(
                $event_manager,
                $this
            ),
            $this->getConfigurationManager(),
            new PlanningPermissionsManager(),
            $scrum_planning_filter,
            new AgileDashboardJSONPermissionsRetriever(
                new AgileDashboardPermissionsRepresentationBuilder(
                    $ugroup_manager,
                    $planning_factory,
                    new PlanningPermissionsRepresentationBuilder(
                        new PlanningPermissionsManager(),
                        PermissionsManager::instance(),
                        new PermissionPerGroupUGroupRepresentationBuilder(
                            $ugroup_manager
                        )
                    )
                ),
            ),
            $service_crumb_builder,
            $admin_crumb_builder,
            new CountElementsModeChecker(new ProjectsCountModeDao()),
            new DBTransactionExecutorWithConnection($db_connection),
            new ArtifactsInExplicitBacklogDao(),
            new ScrumPresenterBuilder(
                $this->getConfigurationManager(),
                $event_manager,
                $planning_factory,
                $project_explicit_backlog_dao,
                new AddToTopBacklogPostActionDao(),
                new MilestonesInSidebarDao(),
            ),
            $event_manager,
            new PlanningUpdater(
                $planning_factory,
                new ArtifactsInExplicitBacklogDao(),
                $planning_dao,
                $planning_permissions_manager,
                new DBTransactionExecutorWithConnection($db_connection)
            ),
            new Planning_RequestValidator($planning_factory, TrackerFactory::instance(), $this->current_user_provider),
            AgileDashboard_XMLExporter::build(),
            new UpdateIsAllowedChecker(
                $planning_factory,
                new BacklogTrackerRemovalChecker(new AddToTopBacklogPostActionDao()),
                $tracker_factory
            ),
            new \Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder(
                $planning_factory,
                $event_manager,
                $scrum_planning_filter,
                $planning_permissions_manager,
                $form_element_factory
            ),
            new \Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator(),
            new BacklogTrackersUpdateChecker(
                Tracker_HierarchyFactory::instance(),
                TrackerFactory::instance(),
                BackendLogger::getDefaultLogger(),
            ),
        );
    }

    /**
     * Builds a new Planning_MilestoneFactory instance.
     * @return Planning_MilestoneFactory
     */
    private function getMilestoneFactory()
    {
        return Planning_MilestoneFactory::build();
    }

    /**
     * @return AgileDashboard_ConfigurationManager
     */
    private function getConfigurationManager()
    {
        return new AgileDashboard_ConfigurationManager(
            new AgileDashboard_ConfigurationDao(),
            EventManager::instance(),
            new MilestonesInSidebarDao(),
            new MilestonesInSidebarDao(),
        );
    }
}
