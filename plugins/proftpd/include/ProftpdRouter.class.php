<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class ProftpdRouter {

    const DEAFULT_CONTROLLER = 'Proftpd_ExplorerController';
    const DEAFULT_ACTION     = 'index';

    private $service;

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request) {
        $no_layout = $request->get('disable_renderer');

        if ($no_layout) {
            $this->displayBody($request);
        } else {
            $this->displayHeader($request);
            $this->displayBody($request);
            $this->displayFooter($request);
        }
    }

    private function displayBody(HTTPRequest $request) {
        if (! $request->get('controller') || ! $request->get('action')) {
            $this->useDefaultRoute($request);
            return;
        }

        $controller = $this->getControllerFromRequest($request);
        $action     = $request->get('action');
        if ($this->doesActionExist($controller, $action)) {
            $controller->$action();
        } else {
            $this->useDefaultRoute($request);
        }
    }

    private function getControllerFromRequest(HTTPRequest $request) {
        switch ($request->get('controller')) {
            case 'explorer':
                return new Proftpd_ExplorerController($request);
            default:
                $this->useDefaultRoute($request);
        }
    }

    private function useDefaultRoute(HTTPRequest $request) {
        $controller_name = self::DEAFULT_CONTROLLER;
        $controller      = new $controller_name($request);
        $action          = self::DEAFULT_ACTION;

        $controller->$action();
    }

    /**
     * @return bool
     */
    private function doesActionExist($controller, $action) {
        return method_exists($controller, $action);
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    private function userIsAdmin(HTTPRequest $request) {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    /**
     * Renders the top banner + navigation for all Agile Dashboard pages.
     *
     * @param MVC2_Controller $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     */
    private function displayHeader(HTTPRequest $request) {
        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_proftpd', 'service_lbl_key'))
            );
        }

        if ($this->userIsAdmin($request)) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Admin'),
                'url'   => AGILEDASHBOARD_BASE_URL .'/?'. http_build_query(array(
                    'group_id' => $request->get('group_id'),
                    'action'   => 'admin',
                ))
            );
        }

        $title       = $GLOBALS['Language']->getText('plugin_proftpd', 'service_lbl_key');
        $toolbar     = array();
        $breadcrumbs = array();

        $service->displayHeader($title, $breadcrumbs, $toolbar);
    }

    /**
     * Renders the bottom footer for all Agile Dashboard pages.
     *
     * @param Codendi_Request $request
     */
    private function displayFooter(HTTPRequest $request) {
        $this->getService($request)->displayFooter();
    }

    /**
     * Retrieves the Proftpd Service instance matching the request group id.
     *
     * @param Codendi_Request $request
     *
     * @return Service
     */
    private function getService(Codendi_Request $request) {
        if ($this->service == null) {
            $project = $request->getProject();
            $this->service = $project->getService('plugin_proftpd');
        }
        return $this->service;
    }
}
?>
