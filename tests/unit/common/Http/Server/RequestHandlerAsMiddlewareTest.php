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

use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class RequestHandlerAsMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testARequestHandlerIsTransformedAsAMiddleware(): void
    {
        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $request_handler   = $this->createMock(RequestHandlerInterface::class);
        $request_handler->expects(self::once())->method('handle')->willReturn($expected_response);

        $middleware = new RequestHandlerAsMiddleware($request_handler);
        $response   = $middleware->process(
            new NullServerRequest(),
            $this->createMock(RequestHandlerInterface::class)
        );

        self::assertSame($expected_response, $response);
    }
}
