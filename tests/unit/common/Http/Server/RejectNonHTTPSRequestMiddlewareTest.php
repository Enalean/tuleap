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

use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class RejectNonHTTPSRequestMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
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
        $uri            = HTTPFactoryBuilder::URIFactory()->createUri('https://example.com');
        $server_request = (new NullServerRequest())
            ->withUri($uri);

        $handler           = $this->createMock(RequestHandlerInterface::class);
        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $handler->method('handle')->with($server_request)->willReturn($expected_response);

        $response = $this->middleware->process($server_request, $handler);
        self::assertEquals($expected_response, $response);
    }

    public function testRequestNotUsingHTTPSIsBlocked(): void
    {
        $uri            = HTTPFactoryBuilder::URIFactory()->createUri('http://insecure.example.com');
        $server_request = (new NullServerRequest())
            ->withUri($uri);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $this->middleware->process($server_request, $handler);
        self::assertEquals(400, $response->getStatusCode());
    }
}
