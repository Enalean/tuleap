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
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\BaseLayout;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class DispatchablePSR15CompatibleTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testRequestIsProcessed(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('middleware', 'OK');
            }
        };
        $emitter = Mockery::mock(EmitterInterface::class);

        $base_layout = Mockery::mock(BaseLayout::class);

        $dispatchable = new class ($emitter, $base_layout, $middleware) extends DispatchablePSR15Compatible {
            /**
             * @var BaseLayout
             */
            private $expected_base_layout;

            public function __construct(
                EmitterInterface $emitter,
                BaseLayout $expected_base_layout,
                MiddlewareInterface ...$middleware_stack
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

        $emitter->shouldReceive('emit')->with(Mockery::on(
            static function (ResponseInterface $response): bool {
                return $response->getHeaderLine('middleware') === 'OK' &&
                    $response->getHeaderLine('dispatchable_got_front_controller_param') === 'front_controller_param';
            }
        ))->andReturn(true)->once();

        $dispatchable->process(
            Mockery::mock(HTTPRequest::class),
            $base_layout,
            ['front_controller_attribute' => 'front_controller_param']
        );
    }

    public function testResponseEmissionFailureThrowsAnException(): void
    {
        $emitter = Mockery::mock(EmitterInterface::class);
        $emitter->shouldReceive('emit')->andReturn(false);

        $dispatchable = new class ($emitter) extends DispatchablePSR15Compatible {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return HTTPFactoryBuilder::responseFactory()->createResponse();
            }
        };

        $this->expectException(PSR15PipelineResponseEmissionException::class);
        $dispatchable->process(
            Mockery::mock(HTTPRequest::class),
            Mockery::mock(BaseLayout::class),
            []
        );
    }
}
