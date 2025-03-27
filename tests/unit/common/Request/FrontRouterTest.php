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

use ColinODell\PsrTestLogger\TestLogger;
use EventManager;
use Exception;
use FastRoute;
use ForgeConfig;
use HTTPRequest;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Plugin;
use PluginManager;
use Project;
use ThemeManager;
use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserWithLoggedInInformationStub;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;
use URLVerification;
use URLVerificationFactory;
use function PHPUnit\Framework\assertInstanceOf;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FrontRouterTest extends TestCase
{
    use ForgeConfigSandbox;

    private FrontRouter $router;
    private URLVerificationFactory&MockObject $url_verification_factory;
    private RouteCollector&MockObject $route_collector;
    private HTTPRequest&MockObject $request;
    private BaseLayout&MockObject $layout;
    private TestLogger $logger;
    private ErrorRendering&MockObject $error_rendering;
    private BurningParrotTheme&MockObject $burning_parrot;
    private PluginManager&MockObject $plugin_manager;
    private RequestInstrumentation&MockObject $request_instrumentation;
    private ThemeManager&\PHPUnit\Framework\MockObject\Stub $theme_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route_collector          = $this->getMockBuilder(RouteCollector::class)
            ->setConstructorArgs([$this->createMock(EventManager::class)])
            ->onlyMethods(['collect'])
            ->getMock();
        $this->url_verification_factory = $this->createMock(URLVerificationFactory::class);
        $this->request                  = $this->createMock(HTTPRequest::class);
        $this->request->method('getFromServer')->willReturn('Some user-agent string');
        $this->layout                  = $this->createMock(BaseLayout::class);
        $this->logger                  = new TestLogger();
        $this->error_rendering         = $this->createMock(ErrorRendering::class);
        $this->theme_manager           = $this->createStub(ThemeManager::class);
        $this->burning_parrot          = $this->createMock(BurningParrotTheme::class);
        $this->plugin_manager          = $this->createMock(PluginManager::class);
        $this->request_instrumentation = $this->createMock(RequestInstrumentation::class);

        $this->theme_manager->method('getBurningParrot')->willReturn($this->burning_parrot);
        $this->theme_manager->method('getTheme')->willReturn($this->layout);

        ForgeConfig::set('codendi_cache_dir', vfsStream::setup()->url());

        $this->router = new FrontRouter(
            $this->route_collector,
            $this->url_verification_factory,
            $this->logger,
            $this->error_rendering,
            $this->theme_manager,
            $this->plugin_manager,
            $this->request_instrumentation,
            ProvideCurrentUserWithLoggedInInformationStub::buildWithUser(UserTestBuilder::anActiveUser()->build())
        );
    }

    public function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['HTTP_ACCEPT']);
        unset($GLOBALS['HTML']);
        unset($GLOBALS['Response']);
        parent::tearDown();
    }

    public function testRouteNotFound(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->route_collector->method('collect');
        $this->error_rendering->expects($this->once())->method('rendersError')->with(self::anything(), self::anything(), 404, self::anything(), self::anything());
        $this->request_instrumentation->expects($this->once())->method('increment')->with(404, self::isInstanceOf(DetectedBrowser::class));

        $this->request->method('isAjax')->willReturn(false);

        $this->router->route($this->request);
    }

    public function testRouteNotFoundAnonymousUserIsNotRedirectedWhenHeaderIsNotProvided(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request->method('getFromServer')->willReturn(false);
        $this->request->method('isAjax')->willReturn(false);

        $this->route_collector->method('collect');
        $this->error_rendering->expects($this->once())->method('rendersError')->with(
            self::anything(),
            self::anything(),
            404,
            self::anything(),
            self::anything()
        );
        $this->request_instrumentation->expects($this->once())->method('increment')->with(404, self::isInstanceOf(DetectedBrowser::class));

        $this->router->route($this->request);
    }

    public function testRouteNotFoundAnonymousUserIsNotRedirectedWhenRequestIsAjax(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request->method('isAjax')->willReturn(true);

        $this->route_collector->method('collect');
        $this->error_rendering->expects($this->once())->method('rendersError')->with(
            self::anything(),
            self::anything(),
            404,
            self::anything(),
            self::anything()
        );
        $this->request_instrumentation->expects($this->once())->method('increment')->with(404, self::isInstanceOf(DetectedBrowser::class));

        $this->router->route($this->request);
    }

    public function testItDispatchRequestWithoutAuthz(): void
    {
        $handler = $this->createMock(DispatchableWithRequestNoAuthz::class);

        $handler->expects($this->once())->method('process');
        $this->request_instrumentation->expects($this->once())->method('increment');

        $this->url_verification_factory->method('getURLVerification')->willReturn($this->createMock(URLVerification::class));

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItChecksWithURLVerificationWhenDispatchingWithRequest(): void
    {
        $handler = $this->createMock(DispatchableWithRequest::class);

        $handler->expects($this->once())->method('process')->with($this->request, $this->layout, []);
        $this->request_instrumentation->expects($this->once())->method('increment');

        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->expects($this->once())->method('assertValidUrl')->with(self::anything(), $this->request, null);
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public function testItRaisesAnErrorWhenHandlerThrows(): void
    {
        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->expects($this->once())->method('assertValidUrl')->with(self::anything(), $this->request, null);
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);
        $this->request_instrumentation->expects($this->once())->method('increment');

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r): true {
            $r->get('/stuff', function (): DispatchableWithRequest {
                return new class implements DispatchableWithRequest {
                    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
                    {
                        throw new Exception('Failure');
                    }
                };
            });
            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->error_rendering->expects($this->once())->method('rendersErrorWithException')->with(
            self::anything(),
            self::anything(),
            500,
            self::anything(),
            self::anything(),
            self::anything()
        );

        $this->router->route($this->request);
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testRaisesAnErrorWhenNoHandlerFound(): void
    {
        $this->request->method('isAjax')->willReturn(false);

        $this->request_instrumentation->expects($this->once())->method('increment');

        $this->route_collector->method('collect');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/does_not_exist';

        $this->error_rendering->expects($this->once())->method('rendersError')->with(
            self::anything(),
            self::anything(),
            404,
            self::anything(),
            self::anything(),
        );

        $this->router->route($this->request);
    }

    public function testItDispatchWithProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $handler = new class ($project) implements DispatchableWithRequest, DispatchableWithProject {
            public ?HTTPRequest $request = null;
            public ?BaseLayout $layout   = null;
            public ?array $variables     = null;

            public function __construct(private readonly Project $project)
            {
            }

            public function getProject(array $variables): Project
            {
                return $this->project;
            }

            public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
            {
                $this->request   = $request;
                $this->layout    = $layout;
                $this->variables = $variables;
            }
        };
        $this->request_instrumentation->expects($this->once())->method('increment');

        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->expects($this->once())->method('assertValidUrl')->with(self::anything(), $this->request, $project);
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
        self::assertEquals($this->request, $handler->request);
        self::assertEquals($this->layout, $handler->layout);
        self::assertEquals([], $handler->variables);
    }

    public function testItProvidesABurningParrotThemeWhenControllerAskForIt(): void
    {
        $handler = new class implements DispatchableWithRequest, DispatchableWithBurningParrot {
            public ?HTTPRequest $request = null;
            public ?BaseLayout $layout   = null;
            public ?array $variables     = null;

            public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
            {
                $this->request   = $request;
                $this->layout    = $layout;
                $this->variables = $variables;
            }
        };
        $this->request_instrumentation->expects($this->once())->method('increment');

        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->method('assertValidUrl');
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
        self::assertEquals($this->request, $handler->request);
        self::assertEquals($this->burning_parrot, $handler->layout);
        self::assertEquals([], $handler->variables);
    }

    public function testItProvidesABurningParrotThemeWhenControllerSelectItExplicitly(): void
    {
        $handler = new class implements DispatchableWithRequest, DispatchableWithThemeSelection {
            public bool $has_processed = false;

            public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
            {
                $this->has_processed = true;
                assertInstanceOf(BurningParrotTheme::class, $layout);
            }

            public function isInABurningParrotPage(HTTPRequest $request, array $variables): bool
            {
                return true;
            }
        };

        $this->request_instrumentation->expects($this->once())->method('increment');

        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->method('assertValidUrl');
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) use ($handler) {
            $r->get('/stuff', function () use ($handler) {
                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);

        self::assertTrue($handler->has_processed);
    }

    public function testItInstantiatePluginsWhenRoutingAPluginRoute(): void
    {
        $controller = $this->createMock(DispatchableWithRequest::class);
        $controller->expects($this->once())->method('process');
        $this->request_instrumentation->expects($this->once())->method('increment');

        $this->plugin_manager->method('getPluginByName')->with('foobar')->willReturn(
            new class ($controller) extends Plugin {
                public function __construct(private readonly DispatchableWithRequest $controller)
                {
                    parent::__construct();
                }

                public function myHandler(): DispatchableWithRequest
                {
                    return $this->controller;
                }
            }
        );

        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->method('assertValidUrl');
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) {
            $r->get('/stuff', ['plugin' => 'foobar', 'handler' => 'myHandler']);

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->router->route($this->request);
    }

    public static function demoHandler(string ...$args): DispatchableWithRequest
    {
        self::assertSame(['some_param1', 'some_param2'], $args);
        return new class implements DispatchableWithRequest
        {
            public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
            {
            }
        };
    }

    public function testItRoutesToRouteCollectorWithParams(): void
    {
        $this->request_instrumentation->expects($this->once())->method('increment');
        $url_verification = $this->createMock(URLVerification::class);
        $url_verification->method('assertValidUrl');
        $this->url_verification_factory->method('getURLVerification')->willReturn($url_verification);

        $counter_process = 0;

        $route_collector = new class ($this->createStub(EventManager::class), $counter_process) extends RouteCollector
        {
            public function __construct(EventManager $event_manager, private int &$counter_process)
            {
                parent::__construct($event_manager);
            }

            public function collect(FastRoute\RouteCollector $r): void
            {
                $r->get('/stuff', ['core' => true, 'handler' => 'myHandler', 'params' => ['some_param1', 'some_param2']]);
            }

            public function myHandler(string ...$args): DispatchableWithRequest
            {
                if ($args !== ['some_param1', 'some_param2']) {
                    throw new \RuntimeException('Unexpected paramaters');
                }

                return new class ($this->counter_process) implements DispatchableWithRequest
                {
                    public function __construct(private int &$counter_process)
                    {
                    }

                    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
                    {
                        $this->counter_process++;
                    }
                };
            }
        };

        $router = new FrontRouter(
            $route_collector,
            $this->url_verification_factory,
            $this->logger,
            $this->error_rendering,
            $this->theme_manager,
            $this->plugin_manager,
            $this->request_instrumentation,
            ProvideCurrentUserWithLoggedInInformationStub::buildWithUser(UserTestBuilder::anActiveUser()->build())
        );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $router->route($this->request);

        self::assertSame(1, $counter_process);
    }

    #[TestWith([200])]
    #[TestWith([500])]
    #[TestWith([302])]
    #[TestWith([419])]
    #[TestWith([101])]
    #[RunInSeparateProcess]
    public function testHTTPStatusCodeIsCorrectlyRecorded(int $status_code): void
    {
        $handler = $this->createMock(DispatchableWithRequestNoAuthz::class);
        $handler->method('process');

        $this->route_collector->method('collect')->with(self::callback(function (FastRoute\RouteCollector $r) use ($handler, $status_code) {
            $r->get('/stuff', function () use ($handler, $status_code) {
                http_response_code($status_code);

                return $handler;
            });

            return true;
        }));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request_instrumentation->expects($this->once())->method('increment')->with($status_code, self::isInstanceOf(DetectedBrowser::class));

        $this->router->route($this->request);
    }

    public function testHttpStatusCodeIsEqualToExceptionCodeIfTheExceptionImplementsCodeIsAValidHTTPStatus(): void
    {
        $exception = new class ('Conflict', 409) extends Exception implements CodeIsAValidHTTPStatus {
        };

        $handler = $this->createMock(DispatchableWithRequestNoAuthz::class);
        $handler->method('process')->willThrowException($exception);

        $this->route_collector->method('collect')->with(self::callback(
            function (FastRoute\RouteCollector $r) use ($handler) {
                $r->get(
                    '/stuff',
                    function () use ($handler) {
                        return $handler;
                    }
                );

                return true;
            }
        ));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request_instrumentation->expects($this->once())->method('increment')->with(
            409,
            self::isInstanceOf(DetectedBrowser::class)
        );

        $this->error_rendering
            ->expects($this->once())
            ->method('rendersErrorWithException')
            ->with(
                self::anything(),
                self::anything(),
                409,
                self::anything(),
                self::anything(),
                self::anything(),
            );

        $this->router->route($this->request);
        self::assertTrue($this->logger->hasErrorThatContains('Caught exception'));
    }

    public function testHttpStatusCodeIs500IfTheExceptionDoesNotImplementCodeIsAValidHTTPStatus(): void
    {
        $exception = new class ('Conflict', 409) extends Exception {
        };

        $handler = $this->createMock(DispatchableWithRequestNoAuthz::class);
        $handler->method('process')->willThrowException($exception);

        $this->route_collector->method('collect')->with(self::callback(
            function (FastRoute\RouteCollector $r) use ($handler) {
                $r->get(
                    '/stuff',
                    function () use ($handler) {
                        return $handler;
                    }
                );

                return true;
            }
        ));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/stuff';

        $this->request_instrumentation->expects($this->once())->method('increment')->with(
            500,
            self::isInstanceOf(DetectedBrowser::class)
        );

        $this->error_rendering
            ->expects($this->once())
            ->method('rendersErrorWithException')
            ->with(
                self::anything(),
                self::anything(),
                500,
                self::anything(),
                self::anything(),
                self::anything(),
            );

        $this->router->route($this->request);
        self::assertTrue($this->logger->hasErrorThatContains('Caught exception'));
    }
}
