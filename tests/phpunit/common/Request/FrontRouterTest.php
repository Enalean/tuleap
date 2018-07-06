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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use FastRoute;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;

class FrontRouterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var FrontRouter
     */
    private $router;
    private $url_verification_factory;
    private $route_collector;
    private $request;
    private $layout;
    private $logger;
    private $error_rendering;
    private $theme_manager;
    private $burning_parrot;

    public function setUp()
    {
        parent::setUp();

        $this->route_collector = Mockery::mock(RouteCollector::class);
        $this->url_verification_factory = Mockery::mock(\URLVerificationFactory::class);
        $this->request = Mockery::mock(\HTTPRequest::class);
        $this->layout  = Mockery::mock(BaseLayout::class);
        $this->logger  = Mockery::mock(\Logger::class);
        $this->error_rendering = Mockery::mock(ErrorRendering::class);
        $this->theme_manager = Mockery::mock(\ThemeManager::class);
        $this->burning_parrot = Mockery::mock(BurningParrotTheme::class);

        $this->request->shouldReceive('getCurrentUser')->andReturn(Mockery::mock(\PFUser::class));
        $this->theme_manager->shouldReceive('getBurningParrot')->andReturn($this->burning_parrot);
        $this->theme_manager->shouldReceive('getTheme')->andReturn($this->layout);

        $this->router = new FrontRouter(
            $this->route_collector,
            $this->url_verification_factory,
            $this->logger,
            $this->error_rendering,
            $this->theme_manager
        );
    }

    public function tearDown()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        unset($GLOBALS['HTML']);
        parent::tearDown();
    }

    public function testRouteNotFound()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->route_collector->shouldReceive('collect');
        $this->error_rendering->shouldReceive('rendersError')->once()->with(Mockery::any(), Mockery::any(), 404, Mockery::any(), Mockery::any());

        $this->router->route($this->request, $this->layout);
    }

    public function testItDispatchRequestWithoutAuthzWhenUserCanAccess()
    {
        $handler = \Mockery::mock(DispatchableWithRequestNoAuthz::class);

        $handler->shouldReceive('userCanAccess')->once()->andReturn(true);
        $handler->shouldReceive('process')->once();

        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn(Mockery::mock(\URLVerification::class));

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request, $this->layout);
    }

    public function testItDispatchRequestWithoutAuthzWhenUserCannotAccess()
    {
        $handler = \Mockery::mock(DispatchableWithRequestNoAuthz::class);

        $handler->shouldReceive('userCanAccess')->once()->andReturn(false);
        $handler->shouldReceive('process')->never();

        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn(Mockery::mock(\URLVerification::class));

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $this->error_rendering->shouldReceive('rendersError')->once()->with(Mockery::any(), Mockery::any(), 403, Mockery::any(), Mockery::any());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request, $this->layout);
    }

    public function testItChecksWithURLVerificationWhenDispatchingWithRequest()
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class);

        $handler->shouldReceive('process')->with($this->request, $this->layout, [])->once();

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->with(Mockery::any(), $this->request, null)->once();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request, $this->layout);
    }

    public function testItRaisesAnErrorWhenHandlerIsUnknown()
    {
        $handler = new \stdClass();

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->with(Mockery::any(), $this->request, null)->once();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->logger->shouldReceive('error')->once();
        $this->error_rendering->shouldReceive('rendersErrorWithException')->once()->with(
            Mockery::any(),
            Mockery::any(),
            500,
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        );

        $this->router->route($this->request, $this->layout);
    }

    public function testItDispatchWithProject()
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class.', '.DispatchableWithProject::class);
        $handler->shouldReceive('process')->with($this->request, $this->layout, [])->once();

        $project = \Mockery::mock(\Project::class);
        $handler->shouldReceive('getProject')->andReturn($project);

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->with(Mockery::any(), $this->request, $project)->once();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request, $this->layout);
    }


    public function testItRaisesAnErrorWhenDispatchWithProjectWithoutProject()
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class.', '.DispatchableWithProject::class);
        $handler->shouldReceive('process')->never();

        $handler->shouldReceive('getProject')->andReturn(null);

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl')->never();
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->logger->shouldReceive('error')->once();
        $this->error_rendering->shouldReceive('rendersErrorWithException')->once()->with(
            Mockery::any(),
            Mockery::any(),
            500,
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        );

        $this->router->route($this->request, $this->layout);
    }

    public function testItProvidesABurningParrotThemeWhenControllerAskForIt()
    {
        $handler = \Mockery::mock(DispatchableWithRequest::class.', '.DispatchableWithBurningParrot::class);

        $handler->shouldReceive('process')->with($this->request, $this->burning_parrot, [])->once();

        $url_verification = Mockery::mock(\URLVerification::class);
        $url_verification->shouldReceive('assertValidUrl');
        $this->url_verification_factory->shouldReceive('getURLVerification')->andReturn($url_verification);

        $this->route_collector->shouldReceive('collect')->with(Mockery::on(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }
}
