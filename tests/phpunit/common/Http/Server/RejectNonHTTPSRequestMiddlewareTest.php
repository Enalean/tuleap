<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class RejectNonHTTPSRequestMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RejectNonHTTPSRequestMiddleware
     */
    private $middleware;

    protected function setUp(): void
    {
        $this->middleware = new RejectNonHTTPSRequestMiddleware(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
    }

    public function testRequestUsingHTTPSCanGoThrough(): void
    {
        $server_request = \Mockery::mock(ServerRequestInterface::class);
        $uri            = HTTPFactoryBuilder::URIFactory()->createUri('https://example.com');
        $server_request->shouldReceive('getUri')->andReturn($uri);

        $handler           = \Mockery::mock(RequestHandlerInterface::class);
        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $handler->shouldReceive('handle')->with($server_request)->andReturn($expected_response);

        $response = $this->middleware->process($server_request, $handler);
        $this->assertEquals($expected_response, $response);
    }

    public function testRequestNotUsingHTTPSIsBlocked(): void
    {
        $server_request = \Mockery::mock(ServerRequestInterface::class);
        $uri            = HTTPFactoryBuilder::URIFactory()->createUri('http://insecure.example.com');
        $server_request->shouldReceive('getUri')->andReturn($uri);

        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $response = $this->middleware->process($server_request, $handler);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
