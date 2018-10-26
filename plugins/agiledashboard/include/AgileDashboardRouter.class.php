<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\FormElement\BurnupCacheGenerator;
use Tuleap\AgileDashboard\FormElement\FormElementController;
use Tuleap\AgileDashboard\Kanban\BreadCrumbBuilder;
use Tuleap\AgileDashboard\Kanban\ShowKanbanController;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;

require_once 'common/plugin/Plugin.class.php';

/**
 * Routes HTTP (and maybe SOAP ?) requests to the appropriate controllers
 * (e.g. PlanningController, MilestoneController...).
 *
 * See AgileDashboardRouter::route()
 *
 * TODO: Layout management should be extracted and moved to controllers or views.
 */
class AgileDashboardRouter
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @param Service
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
     * @var Planning_MilestoneControllerFactory
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

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var PlanningPermissionsManager */
    private $planning_permissions_manager;

    /** @var AgileDashboard_HierarchyChecker */
    private $hierarchy_checker;

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

    public function __construct(
        Plugin $plugin,
        Planning_MilestoneFactory $milestone_factory,
        PlanningFactory $planning_factory,
        Planning_MilestoneControllerFactory $milestone_controller_factory,
        ProjectManager $project_manager,
        AgileDashboard_XMLFullStructureExporter $xml_exporter,
        AgileDashboard_KanbanManager $kanban_manager,
        AgileDashboard_ConfigurationManager $config_manager,
        AgileDashboard_KanbanFactory $kanban_factory,
        PlanningPermissionsManager $planning_permissions_manager,
        AgileDashboard_HierarchyChecker $hierarchy_checker,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        ScrumPlanningFilter $planning_filter,
        AgileDashboardJSONPermissionsRetriever $permissions_retriever,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $admin_crumb_builder
    ) {
        $this->plugin                       = $plugin;
        $this->milestone_factory            = $milestone_factory;
        $this->planning_factory             = $planning_factory;
        $this->milestone_controller_factory = $milestone_controller_factory;
        $this->project_manager              = $project_manager;
        $this->xml_exporter                 = $xml_exporter;
        $this->kanban_manager               = $kanban_manager;
        $this->config_manager               = $config_manager;
        $this->kanban_factory               = $kanban_factory;
        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->hierarchy_checker            = $hierarchy_checker;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
        $this->planning_filter              = $planning_filter;
        $this->permissions_retriever        = $permissions_retriever;
        $this->service_crumb_builder        = $service_crumb_builder;
        $this->admin_crumb_builder          = $admin_crumb_builder;
    }

    /**
     * Routes the given request to the appropriate controller.
     *
     * TODO:
     *   - Use a 'resource' parameter to deduce the controller (e.g. someurl/?resource=planning&id=2 )
     *   - Pass $request to action methods
     *
     * @param Codendi_Request $request
     */
    public function route(Codendi_Request $request) {
        $planning_controller            = $this->buildPlanningController($request);
        $agile_dashboard_xml_controller = new AgileDashboard_XMLController(
            $request,
            $this->planning_factory,
            $this->milestone_factory,
            $this->plugin->getThemePath()
        );

        switch($request->get('action')) {
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
                    if ($request->get('pane') === 'kanban') {
                        $this->renderAction($this->buildController($request), 'adminKanban', $request);
                    } else {
                        $this->renderAction($this->buildController($request), 'adminScrum', $request);
                    }
                } else {
                    $this->renderAction($planning_controller, 'index', $request);
                }
                break;
            case 'export':
                $this->executeAction($agile_dashboard_xml_controller, 'export');
                break;
            case 'import':
                if (! IS_SCRIPT) {
                    $this->executeAction($agile_dashboard_xml_controller, 'importOnlyAgileDashboard');
                } else {
                    $this->executeAction($agile_dashboard_xml_controller, 'importProject');
                }
                break;
            case 'solve-inconsistencies':
                $milestone_controller = $this->milestone_controller_factory->getMilestoneController($request);
                $this->executeAction($milestone_controller, 'solveInconsistencies');
                break;
            case 'updateConfiguration':
                $this->executeAction($this->buildController($request), 'updateConfiguration');
                break;
            case 'createKanban':
                $this->executeAction($this->buildController($request), 'createKanban');
                break;
            case 'showKanban':
                $header_options = array(
                    'body_class'                 => array('agiledashboard_kanban'),
                    Layout::INCLUDE_FAT_COMBINED => false,
                );

                $plugin_path = $this->plugin->getPluginPath();

                $controller = new ShowKanbanController(
                    $request,
                    $this->kanban_factory,
                    TrackerFactory::instance(),
                    new AgileDashboard_PermissionsManager(),
                    $this->service_crumb_builder,
                    new BreadCrumbBuilder($plugin_path)
                );
                $this->renderAction($controller, 'showKanban', $request, array(), $header_options);
                break;
            case 'burnup-cache-generate':
                $this->buildFormElementController()->forceBurnupCacheGeneration($request);
                break;
            case 'permission-per-group':
                if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                    $GLOBALS['Response']->send400JSONErrors(
                        array(
                            'error' => dgettext(
                                'tuleap-agiledashboard',
                                "You don't have permissions to see user groups."
                            )
                        )
                    );
                }

                $this->permissions_retriever->retrieve($request->getProject(), $request->getCurrentUser(), $request->get('selected_ugroup_id'));
                break;
            case 'index':
            default:
                $header_options = array(
                    'body_class' => array('agiledashboard_homepage')
                );
                $this->renderAction($planning_controller, 'index', $request, array(), $header_options);
        }
    }

    /**
     * Returns the page title according to the current controller action name.
     *
     * TODO:
     *   - Use a layout template, and move title retrieval to the appropriate presenters.
     *
     * @param string $action_name The controller action name (e.g. index, show...).
     *
     * @return string
     */
    private function getHeaderTitle(Codendi_Request $request, $action_name) {
        $header_title = array(
            'index'               => $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key'),
            'exportToFile'        => $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key'),
            'adminScrum'          => $GLOBALS['Language']->getText('plugin_agiledashboard', 'AdminScrum'),
            'adminKanban'         => $GLOBALS['Language']->getText('plugin_agiledashboard', 'AdminKanban'),
            'new_'                => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_new'),
            'importForm'          => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_new'),
            'edit'                => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_edit'),
            'show'                => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_show'),
            'showTop'             => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_show'),
            'showKanban'          => $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_show')
        );

        $title = $header_title[$action_name];

        if ($action_name === 'showKanban') {
            return $this->getPageTitleWithKanbanName($request, $title);
        }

        return $title;
    }

    /**
     * Retrieves the Agile Dashboard Service instance matching the request group id.
     *
     * @param Codendi_Request $request
     *
     * @return Service
     */
    private function getService(Codendi_Request $request) {
        if ($this->service == null) {
            $project = $request->getProject();
            $this->service = $project->getService('plugin_agiledashboard');
        }
        return $this->service;
    }

    /**
     * Renders the top banner + navigation for all Agile Dashboard pages.
     *
     * @param BaseController  $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     * @param array           $header_options
     */
    private function displayHeader(
        BaseController $controller,
        Codendi_Request $request,
        $title,
        array $header_options = []
    ) {
        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key'))
            );
        }

        $service->displayHeader($title, $controller->getBreadcrumbs(), [], $header_options);
    }

    private function userIsAdmin(Codendi_Request $request) {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    /**
     * Renders the bottom footer for all Agile Dashboard pages.
     *
     * @param Codendi_Request $request
     */
    private function displayFooter(Codendi_Request $request) {
        $this->getService($request)->displayFooter();
    }

    /**
     * Builds a new Planning_Controller instance.
     *
     * @param Codendi_Request $request
     *
     * @return Planning_Controller
     */
    protected function buildPlanningController(Codendi_Request $request)
    {
        return new Planning_Controller(
            $request,
            $this->planning_factory,
            $this->milestone_factory,
            $this->project_manager,
            $this->xml_exporter,
            $this->plugin->getThemePath(),
            $this->plugin->getPluginPath(),
            $this->kanban_manager,
            $this->config_manager,
            $this->kanban_factory,
            $this->planning_permissions_manager,
            $this->hierarchy_checker,
            $this->scrum_mono_milestone_checker,
            $this->planning_filter,
            TrackerFactory::instance(),
            Tracker_FormElementFactory::instance(),
            $this->service_crumb_builder,
            $this->admin_crumb_builder
        );
    }

    protected function buildController(Codendi_Request $request)
    {
        return new AdminController(
            $request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            TrackerFactory::instance(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $this->planning_factory),
            EventManager::instance(),
            $this->service_crumb_builder,
            $this->admin_crumb_builder
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

    /**
     * Renders the given controller action, with page header/footer.
     *
     * @param MVC2_Controller $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param Codendi_Request $request     The request
     * @param array           $args        Arguments to pass to the controller action method.
     */
    protected function renderAction(MVC2_Controller $controller,
                                                    $action_name,
                                    Codendi_Request $request,
                                    array           $args = array(),
                                    array           $header_options = array()) {
        $content = $this->executeAction($controller, $action_name, $args);
        $header_options = array_merge($header_options, $controller->getHeaderOptions());

        $this->displayHeader($controller, $request, $this->getHeaderTitle($request, $action_name), $header_options);
        echo $content;
        $this->displayFooter($request);
    }

    /**
     * Executes the given controller action, without rendering page header/footer.
     * Useful for actions ending with a redirection instead of page rendering.
     *
     * @param MVC2_Controller $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param array           $args        Arguments to pass to the controller action method.
     */
    protected function executeAction(MVC2_Controller $controller,
                                                     $action_name,
                                     array           $args = array()) {

        return call_user_func_array(array($controller, $action_name), $args);
    }

    /**
     * Routes some milestone-related requests.
     *
     * TODO:
     *   - merge into AgileDashboardRouter::route()
     *
     * @param Codendi_Request $request
     */
    public function routeShowPlanning(Codendi_Request $request) {
        $aid = $request->getValidated('aid', 'int', 0);
        switch ($aid) {
            case -1:
                $controller = new Planning_ArtifactCreationController($this->planning_factory, $request);
                $this->executeAction($controller, 'createArtifact');
                break;
            case 0:
                $controller = new Planning_MilestoneSelectorController($request, $this->milestone_factory);
                $this->executeAction($controller, 'show');
                /* no break */
            default:
                $controller = $this->milestone_controller_factory->getMilestoneController($request);
                $action_arguments = array();
                $this->renderAction($controller, 'show', $request, $action_arguments, array('body_class' => array('agiledashboard_planning')));
        }
    }

    public function routeShowTopPlanning(Codendi_Request $request, $default_controller) {
        $user = $request->getCurrentUser();
        if (! $user) {
            $this->renderAction($default_controller, 'index', $request);
        }

        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key'))
            );
        }

        $controller     = $this->milestone_controller_factory->getVirtualTopMilestoneController($request);
        $header_options = array_merge(
            array('body_class' => array('agiledashboard_planning')),
            $controller->getHeaderOptions()
        );
        $breadcrumbs = $controller->getBreadcrumbs();

        $top_planning_rendered = $this->executeAction($controller, 'showTop', array());
        $service->displayHeader(
            sprintf(
                dgettext('tuleap-agiledashboard', '%s top backlog'),
                $service->getProject()->getUnconvertedPublicName()
            ),
            $breadcrumbs,
            [],
            $header_options
        );
        echo $top_planning_rendered;
        $this->displayFooter($request);
    }

    private function getPageTitleWithKanbanName(Codendi_Request $request, $title)
    {
        $kanban_id = $request->get('id');
        $user      = $request->getCurrentUser();

        try {
            $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
            $title  = $kanban->getName();
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
        }

        return $title;
    }
}
