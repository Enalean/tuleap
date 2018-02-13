<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Request;

use EventManager;
use FastRoute;

class FrontRouter
{
    public function route()
    {
        $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', function () {
                return new \Tuleap\Project\Home();
            });

            $collect_routes = new CollectRoutesEvent($r);
            EventManager::instance()->processEvent($collect_routes);
        });

        // Fetch method and URI from somewhere
        $http_method = $_SERVER['REQUEST_METHOD'];
        $uri         = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        $pos = strpos($uri, '?');
        if (false !== $pos) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $route_info = $dispatcher->dispatch($http_method, $uri);
        if ($route_info[0] === FastRoute\Dispatcher::FOUND) {
            if (is_callable($route_info[1])) {
                $handler = $route_info[1]();
                if ($handler instanceof Dispatchable) {
                    $handler->process($route_info[2]);
                    exit;
                }
            }
            throw new \RuntimeException("No valid handler associated to route $http_method $uri");
        }
    }
}
