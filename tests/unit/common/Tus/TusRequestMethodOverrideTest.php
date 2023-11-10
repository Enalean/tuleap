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

namespace Tuleap\Tus;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\AlwaysSuccessfulRequestHandler;

final class TusRequestMethodOverrideTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testMethodCanBeOverridden(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('X-Http-Method-Override')->willReturn('PATCH');
        $request->method('getMethod')->willReturn('POST');

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $method_overrider = new TusRequestMethodOverride($response_factory);

        $request->expects(self::once())->method('withMethod')->with('PATCH')->willReturnSelf();

        $method_overrider->process($request, new AlwaysSuccessfulRequestHandler($response_factory));
    }

    public function testMethodIsLeftUntouchedWhenThereIsNoOverrideHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('X-Http-Method-Override')->willReturn('');

        $request->expects(self::never())->method('withMethod');

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $method_overrider = new TusRequestMethodOverride($response_factory);

        $method_overrider->process($request, new AlwaysSuccessfulRequestHandler($response_factory));
    }

    public function testRequestIsRejectedIfTheMethodToOverrideIsNotAllowed(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('X-Http-Method-Override')->willReturn('PATCH');
        $request->method('getMethod')->willReturn('GET');

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $method_overrider = new TusRequestMethodOverride($response_factory);

        $response = $method_overrider->process($request, new AlwaysSuccessfulRequestHandler($response_factory));

        self::assertEquals(405, $response->getStatusCode());
    }

    public function testRequestIsRejectedIfTheRequestedOverrideMethodIsNotAllowed(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('X-Http-Method-Override')->willReturn('LOCK');
        $request->method('getMethod')->willReturn('PATCH');

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $method_overrider = new TusRequestMethodOverride($response_factory);

        $response = $method_overrider->process($request, new AlwaysSuccessfulRequestHandler($response_factory));

        self::assertEquals(405, $response->getStatusCode());
    }
}
