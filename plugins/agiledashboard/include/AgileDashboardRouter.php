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
use Tuleap\AgileDashboard\XML\AgileDashboardXMLImporter;
use Tuleap\AgileDashboard\AgileDashboard_XMLController;
use Tuleap\AgileDashboard\AgileDashboardServiceHomepageUrlBuilder;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ConfigurationManager;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\ExplicitBacklog\XMLImporter;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\BurnupCacheGenerator;
use Tuleap\AgileDashboard\FormElement\FormElementController;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarDao;
use Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator;
use Tuleap\AgileDashboard\Planning\BacklogTrackersUpdateChecker;
use Tuleap\AgileDashboard\Planning\MilestoneControllerFactory;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\AgileDashboard\XML\AgileDashboardXMLExporter;
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
class AgileDashboardRouter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private const string PANE_KANBAN = 'kanban';
    private const string PANE_CHARTS = 'charts';
    /**
     * @var Service|null
     */
    private $service;

    public function __construct(
        private readonly Planning_MilestoneFactory $milestone_factory,
        private readonly PlanningFactory $planning_factory,
        private readonly MilestoneControllerFactory $milestone_controller_factory,
        private readonly ProjectManager $project_manager,
        private readonly AgileDashboard_XMLFullStructureExporter $xml_exporter,
        private readonly ConfigurationManager $config_manager,
        private readonly PlanningPermissionsManager $planning_permissions_manager,
        private readonly ScrumPlanningFilter $planning_filter,
        private readonly AgileDashboardJSONPermissionsRetriever $permissions_retriever,
        private readonly AgileDashboardCrumbBuilder $service_crumb_builder,
        private readonly AdministrationCrumbBuilder $admin_crumb_builder,
        private readonly CountElementsModeChecker $count_elements_mode_checker,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly ArtifactsInExplicitBacklogDao $explicit_backlog_dao,
        private readonly ScrumPresenterBuilder $scrum_presenter_builder,
        private readonly EventManager $event_manager,
        private readonly PlanningUpdater $planning_updater,
        private readonly Planning_RequestValidator $planning_request_validator,
        private readonly AgileDashboardXMLExporter $agile_dashboard_exporter,
        private readonly UpdateIsAllowedChecker $root_planning_update_checker,
        private readonly PlanningEditionPresenterBuilder $planning_edition_presenter_builder,
        private readonly UpdateRequestValidator $update_request_validator,
        private readonly BacklogTrackersUpdateChecker $backlog_trackers_update_checker,
        private readonly ProjectHistoryDao $project_history_dao,
        private readonly TrackerFactory $tracker_factory,
    ) {
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
            new AgileDashboardXMLImporter(),
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
                $this->routeShowTopPlanning($request);
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
            $this->project_history_dao,
            $this->tracker_factory
        );
    }

    protected function buildController(Codendi_Request $request)
    {
        $layout = $GLOBALS['Response'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        return new AdminController(
            $request,
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

    public function routeShowTopPlanning(Codendi_Request $request)
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
