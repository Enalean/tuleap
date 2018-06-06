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
use ThemeManager;
use Tuleap\Admin\ProjectCreationModerationDisplayController;
use Tuleap\Admin\ProjectCreationModerationUpdateController;
use Tuleap\Admin\ProjectTemplatesController;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\Layout\BaseLayout;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use URLVerificationFactory;

class FrontRouter
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ThemeManager
     */
    private $theme_manager;
    /**
     * @var URLVerificationFactory
     */
    private $url_verification_factory;

    public function __construct(EventManager $event_manager, ThemeManager $theme_manager, URLVerificationFactory $url_verification_factory)
    {
        $this->event_manager            = $event_manager;
        $this->theme_manager            = $theme_manager;
        $this->url_verification_factory = $url_verification_factory;
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
                            $url_verification->assertValidUrl($_SERVER, $request);

                            if ($handler instanceof Dispatchable) {
                                $handler->process($route_info[2]);
                            } elseif ($handler instanceof DispatchableWithRequest) {
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
        } catch (NotFoundException $exception) {
            (new ErrorRendering(
                $request,
                $this->theme_manager->getBurningParrot($request->getCurrentUser()),
                404,
                _('Not found'),
                $exception->getMessage()
            ))->rendersError();
        } catch (ForbiddenException $exception) {
            (new ErrorRendering(
                $request,
                $this->theme_manager->getBurningParrot($request->getCurrentUser()),
                403,
                _('Forbidden'),
                $exception->getMessage()
            ))->rendersError();
        } catch (\Exception $exception) {
            $code = 500;
            if ($exception->getCode() !== 0) {
                $code = $exception->getCode();
            }
            (new \BackendLogger())->error('Caught exception', $exception);
            (new ErrorRendering(
                $request,
                $this->theme_manager->getBurningParrot($request->getCurrentUser()),
                $code,
                _('Internal server error'),
                _('We are sorry you caught an error, something meaningful was logged for site administrators. You may want got get in touch with them.')
            ))->rendersErrorWithException($exception);
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
            $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
                $r->get('/password_policy/', function () {
                    return new PasswordPolicyDisplayController(
                        new \Tuleap\Admin\AdminPageRenderer,
                        \TemplateRendererFactory::build(),
                        new PasswordConfigurationRetriever(new PasswordConfigurationDAO)
                    );
                });
                $r->post('/password_policy/', function () {
                    return new PasswordPolicyUpdateController(
                        new PasswordConfigurationSaver(new PasswordConfigurationDAO)
                    );
                });
                $r->get('/project-creation/moderation', function () {
                    return new ProjectCreationModerationDisplayController();
                });
                $r->post('/project-creation/moderation', function () {
                    return new ProjectCreationModerationUpdateController();
                });
                $r->get('/project-creation/templates', function () {
                    return new ProjectTemplatesController();
                });
            });

            $collect_routes = new CollectRoutesEvent($r);
            $this->event_manager->processEvent($collect_routes);
        });
    }
}
