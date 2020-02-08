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

namespace Tuleap\ProFTPd;

use HTTPRequest;

class ProftpdRouter
{

    public const DEFAULT_CONTROLLER = 'explorer';
    public const DEFAULT_ACTION     = 'index';

    private $controllers = array();

    public function __construct(array $controllers)
    {
        foreach ($controllers as $controller) {
            $this->controllers[$controller->getName()] = $controller;
        }
    }

    /**
     * Routes the request to the correct controller
     * @return void
     */
    public function route(HTTPRequest $request)
    {
        if (! $request->get('controller') || ! $request->get('action')) {
            $this->useDefaultRoute($request);
            return;
        }

        $controller = $this->getControllerFromRequest($request);
        $action     = $request->get('action');
        if ($this->doesActionExist($controller, $action)) {
            $controller->$action($this->getService($request), $request);
        } else {
            $this->useDefaultRoute($request);
        }
    }

    private function getControllerFromRequest(HTTPRequest $request)
    {
        if (isset($this->controllers[$request->get('controller')])) {
            return $this->controllers[$request->get('controller')];
        } else {
            return $this->controllers[self::DEFAULT_CONTROLLER];
        }
    }

    private function useDefaultRoute(HTTPRequest $request)
    {
        $action = self::DEFAULT_ACTION;
        $this->controllers[self::DEFAULT_CONTROLLER]->$action($this->getService($request), $request);
    }

    /**
     * @return bool
     */
    private function doesActionExist($controller, $action)
    {
        return method_exists($controller, $action);
    }

    /**
     * Retrieves the Proftpd Service instance matching the request group id.
     *
     *
     * @return ServiceProFTPd
     */
    private function getService(HTTPRequest $request)
    {
        return $request->getProject()->getService('plugin_proftpd');
    }
}
