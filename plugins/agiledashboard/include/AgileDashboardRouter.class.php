<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\AdminController;
use Tuleap\AgileDashboard\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarXmlImport;
use Tuleap\AgileDashboard\AgileDashboardServiceHomepageUrlBuilder;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\ExplicitBacklog\XMLImporter;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\BurnupCacheGenerator;
use Tuleap\AgileDashboard\FormElement\FormElementController;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarDao;
use Tuleap\AgileDashboard\Planning\MilestoneControllerFactory;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator;
use Tuleap\AgileDashboard\Planning\BacklogTrackersUpdateChecker;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;

/**
 * Routes HTTP requests to the appropriate controllers
 * (e.g. PlanningController, MilestoneController...).
 *
 * See AgileDashboardRouter::route()
 *
 * TODO: Layout management should be extracted and moved to controllers or views.
 */
class AgileDashboardRouter
{
    private const PANE_KANBAN = 'kanban';
    private const PANE_CHARTS = 'charts';
    /**
     * @var Planning_RequestValidator
     */
    private $planning_request_validator;

    /**
     * @var Service|null
     */
    private $service;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var MilestoneControllerFactory
     */
    private $milestone_controller_factory;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var AgileDashboard_XMLFullStructureExporter
     */
    private $xml_exporter;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var PlanningPermissionsManager */
    private $planning_permissions_manager;

    /**
     * @var ScrumForMonoMilestoneChecker
     */

    private $scrum_mono_milestone_checker;
    /**
     * @var ScrumPlanningFilter
     */
    private $planning_filter;
    /**
     * @var AgileDashboardJSONPermissionsRetriever
     */
    private $permissions_retriever;
    /**
     * @var AgileDashboardCrumbBuilder
     */
    private $service_crumb_builder;
    /**
     * @var AdministrationCrumbBuilder
     */
    private $admin_crumb_builder;

    /**
     * @var CountElementsModeChecker
     */
    private $count_elements_mode_checker;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var ScrumPresenterBuilder
     */
    private $scrum_presenter_builder;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var PlanningUpdater
     */
    private $planning_updater;
    /**
     * @var AgileDashboard_XMLExporter
     */
    private $agile_dashboard_exporter;
    /**
     * @var UpdateIsAllowedChecker
     */
    private $root_planning_update_checker;
    /**
     * @var PlanningEditionPresenterBuilder
     */
    private $planning_edition_presenter_builder;
    /**
     * @var UpdateRequestValidator
     */
    private $update_request_validator;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        PlanningFactory $planning_factory,
        MilestoneControllerFactory $milestone_controller_factory,
        ProjectManager $project_manager,
        AgileDashboard_XMLFullStructureExporter $xml_exporter,
        AgileDashboard_ConfigurationManager $config_manager,
        PlanningPermissionsManager $planning_permissions_manager,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        ScrumPlanningFilter $planning_filter,
        AgileDashboardJSONPermissionsRetriever $permissions_retriever,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $admin_crumb_builder,
        CountElementsModeChecker $count_elements_mode_checker,
        DBTransactionExecutor $transaction_executor,
        ArtifactsInExplicitBacklogDao $explicit_backlog_dao,
        ScrumPresenterBuilder $scrum_presenter_builder,
        EventManager $event_manager,
        PlanningUpdater $planning_updater,
        Planning_RequestValidator $planning_request_validator,
        AgileDashboard_XMLExporter $agile_dashboard_exporter,
        UpdateIsAllowedChecker $root_planning_update_checker,
        PlanningEditionPresenterBuilder $planning_edition_presenter_builder,
        UpdateRequestValidator $update_request_validator,
        private BacklogTrackersUpdateChecker $backlog_trackers_update_checker,
    ) {
        $this->milestone_factory                  = $milestone_factory;
        $this->planning_factory                   = $planning_factory;
        $this->milestone_controller_factory       = $milestone_controller_factory;
        $this->project_manager                    = $project_manager;
        $this->xml_exporter                       = $xml_exporter;
        $this->config_manager                     = $config_manager;
        $this->planning_permissions_manager       = $planning_permissions_manager;
        $this->scrum_mono_milestone_checker       = $scrum_mono_milestone_checker;
        $this->planning_filter                    = $planning_filter;
        $this->permissions_retriever              = $permissions_retriever;
        $this->service_crumb_builder              = $service_crumb_builder;
        $this->admin_crumb_builder                = $admin_crumb_builder;
        $this->count_elements_mode_checker        = $count_elements_mode_checker;
        $this->transaction_executor               = $transaction_executor;
        $this->explicit_backlog_dao               = $explicit_backlog_dao;
        $this->scrum_presenter_builder            = $scrum_presenter_builder;
        $this->event_manager                      = $event_manager;
        $this->planning_updater                   = $planning_updater;
        $this->planning_request_validator         = $planning_request_validator;
        $this->agile_dashboard_exporter           = $agile_dashboard_exporter;
        $this->root_planning_update_checker       = $root_planning_update_checker;
        $this->planning_edition_presenter_builder = $planning_edition_presenter_builder;
        $this->update_request_validator           = $update_request_validator;
    }

    /**
     * Routes the given request to the appropriate controller.
     *
     * TODO:
     *   - Use a 'resource' parameter to deduce the controller (e.g. someurl/?resource=planning&id=2 )
     *   - Pass $request to action methods
     *
     */
    public function route(Codendi_Request $request)
    {
        $planning_controller            = $this->buildPlanningController($request);
        $xml_rng_validator              = new XML_RNGValidator();
        $external_field_extractor       = new ExternalFieldsExtractor($this->event_manager);
        $agile_dashboard_xml_controller = new AgileDashboard_XMLController(
            $request,
            $this->planning_factory,
            $xml_rng_validator,
            $this->agile_dashboard_exporter,
            new AgileDashboard_XMLImporter(),
            $this->planning_request_validator,
            new XMLImporter(
                new ExplicitBacklogDao(),
                new TopBacklogElementsToAddChecker(
                    PlanningFactory::build(),
                    Tracker_ArtifactFactory::instance()
                ),
                new UnplannedArtifactsAdder(
                    new ExplicitBacklogDao(),
                    new ArtifactsInExplicitBacklogDao(),
                    new PlannedArtifactDao()
                )
            ),
            $external_field_extractor,
            $this->event_manager,
            new MilestonesInSidebarXmlImport(new MilestonesInSidebarDao()),
        );

        switch ($request->get('action')) {
            case 'show':
                $this->routeShowPlanning($request);
                break;
            case 'show-top':
                $this->routeShowTopPlanning($request, $planning_controller);
                break;
            case 'new':
                $this->renderAction($planning_controller, 'new_', $request);
                break;
            case 'import-form':
                $this->renderAction($planning_controller, 'importForm', $request);
                break;
            case 'export-to-file':
                $this->executeAction($planning_controller, 'exportToFile');
                break;
            case 'create':
                $this->executeAction($planning_controller, 'create');
                break;
            case 'edit':
                $this->renderAction($planning_controller, 'edit', $request);
                break;
            case 'update':
                $this->executeAction($planning_controller, 'update');
                break;
            case 'delete':
                $this->executeAction($planning_controller, 'delete');
                break;
            case 'admin':
                if ($this->userIsAdmin($request)) {
                    $pane = $request->get('pane');
                    if ($pane === self::PANE_KANBAN) {
                        throw new \Tuleap\Request\NotFoundException();
                    } elseif ($pane === self::PANE_CHARTS) {
                        $this->renderAction($this->buildController($request), 'adminCharts', $request);
                    } else {
                        $this->renderAction($this->buildController($request), 'adminScrum', $request);
                    }
                } else {
                    $GLOBALS['Response']->redirect(AGILEDASHBOARD_BASE_URL . '/?group_id=' . urlencode($request->get('group_id')));
                }
                break;
            case 'export':
                $this->executeAction($agile_dashboard_xml_controller, 'export');
                break;
            case 'import':
                if (! IS_SCRIPT) {
                    $this->executeAction($agile_dashboard_xml_controller, 'importOnlyAgileDashboard');
                } else {
                    $this->executeAction(
                        $agile_dashboard_xml_controller,
                        'importProject',
                        [
                            $request->get('artifact_id_mapping'),
                            $request->get('logger'),
                        ]
                    );
                }
                break;
            case 'solve-inconsistencies':
                $milestone_controller = $this->milestone_controller_factory->getMilestoneController($request);
                $this->executeAction($milestone_controller, 'solveInconsistencies');
                break;
            case 'updateConfiguration':
                $this->executeAction($this->buildController($request), 'updateConfiguration');
                break;
            case 'showKanban':
                $GLOBALS['Response']->permanentRedirect('/kanban/' . urlencode((string) $request->get('id')));
                break;
            case 'burnup-cache-generate':
                $this->buildFormElementController()->forceBurnupCacheGeneration($request);
                break;
            case 'permission-per-group':
                if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                    $GLOBALS['Response']->send400JSONErrors(
                        [
                            'error' => [
                                'message' => dgettext(
                                    'tuleap-agiledashboard',
                                    "You don't have permissions to see user groups."
                                ),
                            ],
                        ]
                    );
                }

                $this->permissions_retriever->retrieve($request->getProject(), $request->getCurrentUser(), $request->get('selected_ugroup_id'));
                break;
            case 'index':
            default:
                $project = $request->getProject();
                $layout  = $GLOBALS['Response'];
                assert($layout instanceof \Tuleap\Layout\BaseLayout);

                $layout->redirect(AgileDashboardServiceHomepageUrlBuilder::getTopBacklogUrl($project));
        }
    }

    /**
     * Retrieves the Agile Dashboard Service instance matching the request group id.
     *
     *
     * @return Service
     */
    private function getService(Codendi_Request $request)
    {
        if ($this->service == null) {
            $project       = $request->getProject();
            $this->service = $project->getService('plugin_agiledashboard');
            assert($this->service instanceof Service);
        }
        return $this->service;
    }

    private function displayHeader(
        Codendi_Request $request,
        string $title,
        BreadCrumbCollection $breadcrumbs,
        \Tuleap\Layout\HeaderConfiguration $header_configuration,
    ): void {
        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    dgettext('tuleap-agiledashboard', 'Backlog'),
                )
            );
        }

        $service->displayHeader($title, $breadcrumbs, [], $header_configuration);
    }

    private function userIsAdmin(Codendi_Request $request): bool
    {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    private function displayFooter(Codendi_Request $request): void
    {
        $this->getService($request)->displayFooter();
    }

    protected function buildPlanningController(Codendi_Request $request): Planning_Controller
    {
        return new Planning_Controller(
            $request,
            $this->planning_factory,
            $this->project_manager,
            $this->xml_exporter,
            $this->planning_permissions_manager,
            $this->scrum_mono_milestone_checker,
            $this->planning_filter,
            Tracker_FormElementFactory::instance(),
            $this->service_crumb_builder,
            $this->admin_crumb_builder,
            $this->transaction_executor,
            $this->explicit_backlog_dao,
            $this->planning_updater,
            $this->event_manager,
            $this->planning_request_validator,
            $this->root_planning_update_checker,
            $this->planning_edition_presenter_builder,
            $this->update_request_validator,
            $this->backlog_trackers_update_checker,
        );
    }

    protected function buildController(Codendi_Request $request)
    {
        $layout = $GLOBALS['Response'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        return new AdminController(
            $request,
            $this->planning_factory,
            $this->config_manager,
            EventManager::instance(),
            $this->service_crumb_builder,
            $this->admin_crumb_builder,
            $this->count_elements_mode_checker,
            $this->scrum_presenter_builder,
            $layout,
        );
    }

    /**
     * @return FormElementController
     */
    private function buildFormElementController()
    {
        return new FormElementController(
            Tracker_ArtifactFactory::instance(),
            new BurnupCacheGenerator(SystemEventManager::instance())
        );
    }

    protected function renderAction(
        BaseController $controller,
        string $action_name,
        Codendi_Request $request,
    ): void {
        $this->executeAction(
            $controller,
            $action_name,
            [
                function (string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration) use ($request): void {
                    $this->displayHeader($request, $title, $breadcrumbs, $header_configuration);
                },
                function () use ($request): void {
                    $this->displayFooter($request);
                },
            ]
        );
        return;
    }

    /**
     * Executes the given controller action, without rendering page header/footer.
     * Useful for actions ending with a redirection instead of page rendering.
     *
     * @param MVC2_Controller $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param array           $args        Arguments to pass to the controller action method.
     */
    protected function executeAction(
        MVC2_Controller $controller,
        $action_name,
        array $args = [],
    ) {
        return call_user_func_array([$controller, $action_name], $args);
    }

    /**
     * Routes some milestone-related requests.
     *
     * TODO:
     *   - merge into AgileDashboardRouter::route()
     *
     */
    public function routeShowPlanning(Codendi_Request $request)
    {
        $aid = $request->getValidated('aid', 'int', 0);
        switch ($aid) {
            case 0:
                $controller = new Planning_MilestoneSelectorController($request, $this->milestone_factory);
                $this->executeAction($controller, 'show');
                /* no break */
            default:
                $controller = $this->milestone_controller_factory->getMilestoneController($request);
                $this->renderAction($controller, 'show', $request);
        }
    }

    public function routeShowTopPlanning(Codendi_Request $request, $default_controller)
    {
        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    dgettext('tuleap-agiledashboard', 'Backlog'),
                )
            );
        }

        $controller = $this->milestone_controller_factory->getVirtualTopMilestoneController($request);

        $controller->showTop(
            function (string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration) use ($request): void {
                $this->displayHeader($request, $title, $breadcrumbs, $header_configuration);
            },
            function () use ($request): void {
                $this->displayFooter($request);
            }
        );
    }
}
