<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/plugin/Plugin.class.php';

/**
 * Routes HTTP (and maybe SOAP ?) requests to the appropriate controllers
 * (e.g. PlanningController, MilestoneController...).
 *
 * See AgileDashboardRouter::route()
 *
 * TODO: Layout management should be extracted and moved to controllers or views.
 */
class AgileDashboardRouter {

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
     * @var Planning_ShortAccessFactory
     */
    private $planning_shortaccess_factory;

    /**
     * @var Planning_MilestoneControllerFactory
     */
    private $milestone_controller_factory;

    public function __construct(
        Plugin $plugin,
        Planning_MilestoneFactory $milestone_factory,
        PlanningFactory $planning_factory,
        Planning_ShortAccessFactory $planning_shortaccess_factory,
        Planning_MilestoneControllerFactory $milestone_controller_factory
    ) {
        $this->plugin                        = $plugin;
        $this->milestone_factory             = $milestone_factory;
        $this->planning_factory              = $planning_factory;
        $this->planning_shortaccess_factory  = $planning_shortaccess_factory;
        $this->milestone_controller_factory  = $milestone_controller_factory;
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
        $controller                     = $this->buildController($request);
        $agile_dashboard_xml_controller = new AgileDashboard_XMLController($request, $this->planning_factory, $this->milestone_factory, $this->plugin->getThemePath());

        switch($request->get('action')) {
            case 'show':
                $this->routeShowPlanning($request);
                break;
            case 'show-top':
                $this->routeShowTopPlanning($request, $controller);
                break;
            case 'new':
                $this->renderAction($controller, 'new_', $request);
                break;
            case 'import-form':
                $this->renderAction($controller, 'importForm', $request);
                break;
            case 'create':
                $this->executeAction($controller, 'create');
                break;
            case 'edit':
                $this->renderAction($controller, 'edit', $request);
                break;
            case 'update':
                $this->executeAction($controller, 'update');
                break;
            case 'delete':
                $this->executeAction($controller, 'delete');
                break;
            case 'admin':
                if ($this->userIsAdmin($request)) {
                    $this->renderAction($controller, 'admin', $request);
                } else {
                    $this->renderAction($controller, 'index', $request);
                }
                break;
            case 'generate_systray_data':
                $this->executeAction($controller, 'generateSystrayData', $request->params);
                break;
            case 'export':
                $this->executeAction($agile_dashboard_xml_controller, 'export');
                break;
            case 'import':
                $this->executeAction($agile_dashboard_xml_controller, 'import');
                break;
            case 'submilestonedata' :
                $milestone_controller = $this->milestone_controller_factory->getMilestoneController($request);
                $this->executeAction($milestone_controller, 'submilestonedata');
                break;
            case 'get-more-milestones':
                $this->executeAction($controller, 'getMoreMilestones');
                break;
            case 'solve-inconsistencies':
                $milestone_controller = $this->milestone_controller_factory->getMilestoneController($request);
                $this->executeAction($milestone_controller, 'solveInconsistencies');
                break;
            case 'index':
            default:
                $this->renderAction($controller, 'index', $request);
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
    private function getHeaderTitle($action_name) {
        $header_title = array(
            'index'     => $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key'),
            'admin'     => $GLOBALS['Language']->getText('plugin_agiledashboard', 'Admin'),
            'new_'      => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_new'),
            'importForm'=> $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_new'),
            'edit'      => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_edit'),
            'show'      => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_show'),
            'showTop'   => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_show')
        );

        return $header_title[$action_name];
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
     * @param MVC2_Controller $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     */
    private function displayHeader(MVC2_Controller $controller,
                                   Codendi_Request $request,
                                                   $title) {
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

        $toolbar     = array();
        $breadcrumbs = $controller->getBreadcrumbs($this->plugin->getPluginPath());
        if ($this->userIsAdmin($request)) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Admin'),
                'url'   => AGILEDASHBOARD_BASE_URL .'/?'. http_build_query(array(
                    'group_id' => $request->get('group_id'),
                    'action'   => 'admin',
                ))
            );
        }
        $service->displayHeader($title, $breadcrumbs->getCrumbs(), $toolbar);
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
    protected function buildController(Codendi_Request $request) {
        return new Planning_Controller(
            $request,
            $this->planning_factory,
            $this->planning_shortaccess_factory,
            $this->milestone_factory,
            $this->plugin->getThemePath()
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
                                    array           $args = array()) {
        $content = $this->executeAction($controller, $action_name, $args);

        $this->displayHeader($controller, $request, $this->getHeaderTitle($action_name));
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
                $this->renderAction($controller, 'show', $request, $action_arguments);
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

        $toolbar     = array();
        if ($this->userIsAdmin($request)) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Admin'),
                'url'   => AGILEDASHBOARD_BASE_URL .'/?'. http_build_query(array(
                    'group_id' => $request->get('group_id'),
                    'action'   => 'admin',
                ))
            );
        }

        $no_breadcrumbs = new BreadCrumb_NoCrumb();
        $service->displayHeader($this->getHeaderTitle('showTop'), $no_breadcrumbs, $toolbar);
        $controller = $this->milestone_controller_factory->getVirtualTopMilestoneController($request);

        $this->executeAction($controller, 'showTop', array());
        $this->displayFooter($request);
    }
}

?>
