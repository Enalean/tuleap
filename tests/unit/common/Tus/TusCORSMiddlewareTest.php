<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tus;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class TusCORSMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testTusInformationIsAdded(): void
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();

        $request_handler_response = $response_factory->createResponse(200);

        $request_handler = $this->createMock(RequestHandlerInterface::class);
        $request_handler->method('handle')->willReturn($request_handler_response);

        $middleware = new TusCORSMiddleware();

        $response = $middleware->process($this->createMock(ServerRequestInterface::class), $request_handler);

        self::assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        self::assertTrue($response->hasHeader('Access-Control-Allow-Headers'));
        self::assertTrue($response->hasHeader('Access-Control-Expose-Headers'));
    }

    public function testAllowedAndExposedHeadersAreNotOverwritten()
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();

        $request_handler_response = $response_factory->createResponse(200)
            ->withHeader('Access-Control-Allow-Headers', 'MyAllowedHeader')
            ->withHeader('Access-Control-Expose-Headers', 'MyExposedHeader');

        $request_handler = $this->createMock(RequestHandlerInterface::class);
        $request_handler->method('handle')->willReturn($request_handler_response);

        $middleware = new TusCORSMiddleware();

        $response = $middleware->process($this->createMock(ServerRequestInterface::class), $request_handler);

        self::assertContains('MyAllowedHeader', $response->getHeader('Access-Control-Allow-Headers'));
        self::assertContains('MyExposedHeader', $response->getHeader('Access-Control-Expose-Headers'));
    }
}
