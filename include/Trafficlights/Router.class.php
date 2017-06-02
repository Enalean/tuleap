<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use Plugin;
use Codendi_Request;
use MVC2_Controller;

class Router {

    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param \Service
     */
    private $service;

    public function __construct(
        Plugin $plugin,
        Config $config
    ) {
        $this->config = $config;
        $this->plugin = $plugin;
    }

    public function route(Codendi_Request $request) {
        switch ($request->get('action')) {
            case 'admin':
                $controller = new AdminController($request, $this->config);
                $this->renderAction($controller, 'admin', $request);
                break;
            case 'admin-update':
                $controller = new AdminController($request, $this->config);
                $this->executeAction($controller, 'update');
                $this->renderIndex($request);
                break;
            default:
                $this->renderIndex($request);
        }
    }

    private function renderIndex(Codendi_Request $request) {
        $controller = new IndexController($request, $this->config);
        $args = array('milestone_id' => (int)$request->getValidated('milestone_id', 'int', 0));
        $this->renderAction($controller, 'index', $request, $args);
    }

    /**
     * Renders the given controller action, with page header/footer.
     *
     * @param MVC2_Controller $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param Codendi_Request $request     The request
     * @param array           $args        Arguments to pass to the controller action method.
     */
    private function renderAction(
        MVC2_Controller $controller,
        $action_name,
        Codendi_Request $request,
        array $args = array()
    ) {
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
    private function executeAction(
        MVC2_Controller $controller,
        $action_name,
        array $args = array()
    ) {
        return call_user_func_array(array($controller, $action_name), $args);
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
            'index' => $GLOBALS['Language']->getText('plugin_trafficlights', 'service_lbl_key'),
            'admin' => $GLOBALS['Language']->getText('global', 'Admin')
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
            $this->service = $project->getService('plugin_trafficlights');
        }
        return $this->service;
    }

    /**
     * Renders the top banner + navigation for all pages.
     *
     * @param MVC2_Controller $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     */
    private function displayHeader(
        MVC2_Controller $controller,
        Codendi_Request $request,
        $title
    ) {
        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_trafficlights', 'service_lbl_key'))
            );
        }

        $toolbar     = array();
        $breadcrumbs = $controller->getBreadcrumbs($this->plugin->getPluginPath());
        if ($this->userIsAdmin($request)) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Admin'),
                'url'   => TRAFFICLIGHTS_BASE_URL .'/?'. http_build_query(array(
                    'group_id' => $request->get('group_id'),
                    'action'   => 'admin',
                ))
            );
        }
        $service->displayHeader($title, $breadcrumbs->getCrumbs(), $toolbar, array('body_class' => array('trafficlights')));
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
}
