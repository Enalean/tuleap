<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Request;

use HTTPRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\BaseLayout;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class DispatchablePSR15CompatibleTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testRequestIsProcessed(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('middleware', 'OK');
            }
        };

        $emitter      = $this->createMock(EmitterInterface::class);
        $base_layout  = $this->createMock(BaseLayout::class);
        $dispatchable = new class ($emitter, $base_layout, $middleware) extends DispatchablePSR15Compatible {
            /**
             * @var BaseLayout
             */
            private $expected_base_layout;

            public function __construct(
                EmitterInterface $emitter,
                BaseLayout $expected_base_layout,
                MiddlewareInterface ...$middleware_stack,
            ) {
                parent::__construct($emitter, ...$middleware_stack);
                $this->expected_base_layout = $expected_base_layout;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                TestCase::assertSame($this->expected_base_layout, $request->getAttribute(BaseLayout::class));
                return HTTPFactoryBuilder::responseFactory()->createResponse()->withHeader(
                    'dispatchable_got_front_controller_param',
                    $request->getAttribute('front_controller_attribute')
                );
            }
        };

        $emitter->expects(self::once())->method('emit')->with(self::callback(
            static function (ResponseInterface $response): bool {
                return $response->getHeaderLine('middleware') === 'OK' &&
                    $response->getHeaderLine('dispatchable_got_front_controller_param') === 'front_controller_param';
            }
        ))->willReturn(true);

        $dispatchable->process(
            $this->createMock(HTTPRequest::class),
            $base_layout,
            ['front_controller_attribute' => 'front_controller_param']
        );
    }

    public function testResponseEmissionFailureThrowsAnException(): void
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->method('emit')->willReturn(false);

        $dispatchable = new class ($emitter) extends DispatchablePSR15Compatible {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return HTTPFactoryBuilder::responseFactory()->createResponse();
            }
        };

        $this->expectException(PSR15PipelineResponseEmissionException::class);
        $dispatchable->process(
            $this->createMock(HTTPRequest::class),
            $this->createMock(BaseLayout::class),
            []
        );
    }
}
