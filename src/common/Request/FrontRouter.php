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
use HTTPRequest;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\Layout\BaseLayout;

class FrontRouter
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function route(HTTPRequest $request, BaseLayout $layout)
    {
        try {
            $route_info = $this->getRouteInfo();
            if ($route_info[0] === FastRoute\Dispatcher::FOUND) {
                if (is_callable($route_info[1])) {
                    $handler = $route_info[1]();
                    if ($handler instanceof Dispatchable) {
                        $handler->process($route_info[2]);
                    } elseif ($handler instanceof DispatchableWithRequest) {
                        $handler->process($request, $layout, $route_info[2]);
                    } else {
                        throw new \RuntimeException('No valid handler associated to route');
                    }
                } else {
                    throw new \RuntimeException('No valid handler associated to route');
                }
            }
        } catch (\Exception $exception) {
            (new \BackendLogger())->error('Unable to route', $exception);
            $layout->rendersError500($exception);
        }
    }

    private function getRouteInfo()
    {
        // Fetch method and URI from somewhere
        $http_method = $_SERVER['REQUEST_METHOD'];
        $uri         = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        $pos = strpos($uri, '?');
        if (false !== $pos) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        return $this->getDispatcher()->dispatch($http_method, $uri);
    }

    private function getDispatcher()
    {
        return FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
            $r->get('/', function () {
                return new SiteHomepageController();
            });
            $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', function () {
                return new \Tuleap\Project\Home();
            });

            $collect_routes = new CollectRoutesEvent($r);
            $this->event_manager->processEvent($collect_routes);
        });
    }
}
