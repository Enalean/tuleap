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

namespace Tuleap\Http\Server;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class MiddlewareDispatcherTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testRequestIsDispatched(): void
    {
        $passthrough_middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
        $expected_response             = HTTPFactoryBuilder::responseFactory()->createResponse();
        $response_generator_middleware = Mockery::mock(MiddlewareInterface::class);
        $response_generator_middleware->shouldReceive('process')->andReturn($expected_response)->once();
        $never_called_middleware = Mockery::mock(MiddlewareInterface::class);
        $never_called_middleware->shouldNotReceive('process');

        $dispatcher = new MiddlewareDispatcher(
            $passthrough_middleware,
            $response_generator_middleware,
            $never_called_middleware
        );
        $response = $dispatcher->handle(Mockery::mock(ServerRequestInterface::class));
        $this->assertSame($expected_response, $response);
    }

    public function testMiddlewareStackCannotBeEmpty(): void
    {
        $this->expectException(EmptyMiddlewareStackException::class);
        new MiddlewareDispatcher();
    }

    public function testFinalMiddlewareInTheStackMustGenerateAResponse(): void
    {
        $passthrough_middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $dispatcher = new MiddlewareDispatcher($passthrough_middleware);
        $this->expectException(MissingMiddlewareResponseException::class);
        $dispatcher->handle(Mockery::mock(ServerRequestInterface::class));
    }
}
