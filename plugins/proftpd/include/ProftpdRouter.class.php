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

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request) {
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
}
?>