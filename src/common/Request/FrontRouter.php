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

use FastRoute;
use HTTPRequest;
use Logger;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Layout\BaseLayout;
use URLVerificationFactory;

class FrontRouter
{
    /**
     * @var URLVerificationFactory
     */
    private $url_verification_factory;
    /**
     * @var RouteCollector
     */
    private $route_collector;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ErrorRendering
     */
    private $error_rendering;

    public function __construct(
        RouteCollector $route_collector,
        URLVerificationFactory $url_verification_factory,
        Logger $logger,
        ErrorRendering $error_rendering
    ) {
        $this->route_collector          = $route_collector;
        $this->url_verification_factory = $url_verification_factory;
        $this->logger                   = $logger;
        $this->error_rendering          = $error_rendering;
    }

    public function route(HTTPRequest $request, BaseLayout $layout)
    {
        try {
            $route_info = $this->getRouteInfo();
            switch ($route_info[0]) {
                case FastRoute\Dispatcher::NOT_FOUND:
                    throw new NotFoundException(_('The page you are looking for does not exist'));
                    break;
                case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    throw new \RuntimeException('This route does not support '.$_SERVER['REQUEST_METHOD'], 405);
                    break;
                case FastRoute\Dispatcher::FOUND:
                    if (is_callable($route_info[1])) {
                        $handler = $route_info[1]();
                        $url_verification = $this->url_verification_factory->getURLVerification($_SERVER);
                        if ($handler instanceof DispatchableWithRequestNoAuthz) {
                            if ($handler->userCanAccess($url_verification, $request, $route_info[2])) {
                                $handler->process($request, $layout, $route_info[2]);
                            } else {
                                throw new ForbiddenException();
                            }
                        } else {
                            $project = null;
                            if ($handler instanceof DispatchableWithProject) {
                                $project = $handler->getProject($request, $route_info[2]);
                                if (! $project instanceof \Project) {
                                    throw new \RuntimeException('DispatchableWithProject::getProject must return a project, null received');
                                }
                            }
                            $url_verification->assertValidUrl($_SERVER, $request, $project);

                            if ($handler instanceof DispatchableWithRequest) {
                                $handler->process($request, $layout, $route_info[2]);
                            } else {
                                throw new \RuntimeException('No valid handler associated to route');
                            }
                        }
                    } else {
                        throw new \RuntimeException('No valid handler associated to route');
                    }
                    break;
            }
            RequestInstrumentation::increment(200);
        } catch (NotFoundException $exception) {
            RequestInstrumentation::increment(404);
            $this->error_rendering->rendersError($request, 404, _('Not found'), $exception->getMessage());
        } catch (ForbiddenException $exception) {
            RequestInstrumentation::increment(403);
            $this->error_rendering->rendersError($request, 403, _('Forbidden'), $exception->getMessage());
        } catch (\Exception $exception) {
            $code = 500;
            if ($exception->getCode() !== 0) {
                $code = $exception->getCode();
            }
            RequestInstrumentation::increment($code);
            $this->logger->error('Caught exception', $exception);
            $this->error_rendering->rendersErrorWithException(
                $request,
                $code,
                _('Internal server error'),
                _('We are sorry you caught an error, something meaningful was logged for site administrators. You may want got get in touch with them.'),
                $exception
            );
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
            $this->route_collector->collect($r);
        });
    }
}
