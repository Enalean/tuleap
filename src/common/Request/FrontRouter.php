<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Backend;
use FastRoute;
use HTTPRequest;
use PluginManager;
use Psr\Log\LoggerInterface;
use ThemeManager;
use Tuleap\Layout\ErrorRendering;
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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ErrorRendering
     */
    private $error_rendering;
    /**
     * @var ThemeManager
     */
    private $theme_manager;
    /**
     * @var PluginManager
     */
    private $plugin_manager;
    /**
     * @var RequestInstrumentation
     */
    private $request_instrumentation;


    public function __construct(
        RouteCollector $route_collector,
        URLVerificationFactory $url_verification_factory,
        LoggerInterface $logger,
        ErrorRendering $error_rendering,
        ThemeManager $theme_manager,
        PluginManager $plugin_manager,
        RequestInstrumentation $request_instrumentation
    ) {
        $this->route_collector          = $route_collector;
        $this->url_verification_factory = $url_verification_factory;
        $this->logger                   = $logger;
        $this->error_rendering          = $error_rendering;
        $this->theme_manager            = $theme_manager;
        $this->plugin_manager           = $plugin_manager;
        $this->request_instrumentation  = $request_instrumentation;
    }

    public function route(HTTPRequest $request)
    {
        try {
            $route_info = $this->getRouteInfo();
            switch ($route_info[0]) {
                case FastRoute\Dispatcher::NOT_FOUND:
                    throw new NotFoundException(_('The page you are looking for does not exist'));
                    break;
                case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    throw new \RuntimeException('This route does not support ' . $_SERVER['REQUEST_METHOD'], 405);
                    break;
                case FastRoute\Dispatcher::FOUND:
                    if (is_callable($route_info[1])) {
                        $handler = $route_info[1]();
                        $this->routeHandler($request, $handler, $route_info);
                    } else {
                        if (is_array($route_info[1])) {
                            if (isset($route_info[1]['core'])) {
                                $handler_method = $route_info[1]['handler'];
                                $this->routeHandler(
                                    $request,
                                    $this->route_collector->$handler_method(...$route_info[1]['params']),
                                    $route_info
                                );
                            } elseif (isset($route_info[1]['plugin']) && isset($route_info[1]['handler'])) {
                                $this->routeHandler(
                                    $request,
                                    $this->getPluginHandler($route_info[1]['plugin'], $route_info[1]['handler']),
                                    $route_info
                                );
                            }
                        }
                    }
                    break;
            }
            $http_response_code = http_response_code();
            assert(is_int($http_response_code));
            $this->request_instrumentation->increment($http_response_code);
        } catch (NotFoundException $exception) {
            $this->request_instrumentation->increment(404);
            $this->error_rendering->rendersError(
                $this->getBurningParrotTheme($request),
                $request,
                404,
                _('Not found'),
                $exception->getMessage()
            );
        } catch (ForbiddenException $exception) {
            $this->request_instrumentation->increment(403);
            $this->error_rendering->rendersError(
                $this->getBurningParrotTheme($request),
                $request,
                403,
                _('Forbidden'),
                $exception->getMessage()
            );
        } catch (\Exception $exception) {
            $code = 500;
            $exception_code = (int) $exception->getCode();
            if ($exception_code !== 0) {
                $code = $exception_code;
            }
            $this->request_instrumentation->increment($code);
            $this->logger->error('Caught exception', ['exception' => $exception]);
            $this->error_rendering->rendersErrorWithException(
                $this->getBurningParrotTheme($request),
                $request,
                $code,
                _('Internal server error'),
                _('We are sorry you caught an error, something meaningful was logged for site administrators. You may want got get in touch with them.'),
                $exception
            );
        }
    }

    private function getBurningParrotTheme(HTTPRequest $request)
    {
        return $this->theme_manager->getBurningParrot($request->getCurrentUser());
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
        return FastRoute\cachedDispatcher(
            function (FastRoute\RouteCollector $r) {
                $this->route_collector->collect($r);
            },
            [
                'cacheFile' => self::getCacheFile(),
            ]
        );
    }

    public static function invalidateCache(): void
    {
        if (file_exists(self::getCacheFile())) {
            unlink(self::getCacheFile());
        }
    }

    private static function getCacheFile(): string
    {
        return \ForgeConfig::getCacheDir() . '/web_routes.php';
    }

    public static function restoreOwnership(LoggerInterface $logger, Backend $backend): void
    {
        if (file_exists(self::getCacheFile())) {
            $logger->debug('Restore ownership on ' . self::getCacheFile());
            $backend->changeOwnerGroupMode(
                self::getCacheFile(),
                \ForgeConfig::getApplicationUserLogin(),
                \ForgeConfig::getApplicationUserLogin(),
                0640
            );
        }
    }

    /**
     * @param             $handler
     * @param array       $route_info
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    private function routeHandler(HTTPRequest $request, DispatchableWithRequest $handler, array $route_info)
    {
        if ($handler instanceof DispatchableWithBurningParrot) {
            $layout = $this->getBurningParrotTheme($request);
        } else {
            $layout = $this->theme_manager->getTheme($request->getCurrentUser());
        }
        $GLOBALS['HTML'] = $GLOBALS['Response'] = $layout;

        if ($handler instanceof DispatchableWithRequestNoAuthz) {
            $handler->process($request, $layout, $route_info[2]);
        } else {
            $project = null;
            if ($handler instanceof DispatchableWithProject) {
                $project = $handler->getProject($route_info[2]);
            }
            $url_verification = $this->url_verification_factory->getURLVerification($_SERVER);
            $url_verification->assertValidUrl($_SERVER, $request, $project);

            if ($handler instanceof DispatchableWithRequest) {
                $handler->process($request, $layout, $route_info[2]);
            } else {
                throw new \RuntimeException('No valid handler associated to route');
            }
        }
    }

    private function getPluginHandler(string $plugin, string $handler)
    {
        $plugin = $this->plugin_manager->getPluginByName($plugin);
        return $plugin->$handler();
    }
}
