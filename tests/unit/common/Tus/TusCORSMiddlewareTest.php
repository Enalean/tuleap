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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;

class TusCORSMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $response_factory;

    public function testTusInformationIsAdded()
    {
        $this->response_factory = HTTPFactoryBuilder::responseFactory();

        $request_handler_response = $this->response_factory->createResponse(200);

        $request_handler = \Mockery::mock(RequestHandlerInterface::class);
        $request_handler->shouldReceive('handle')->andReturns($request_handler_response);

        $middleware = new TusCORSMiddleware();

        $response = $middleware->process(\Mockery::mock(ServerRequestInterface::class), $request_handler);

        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Headers'));
        $this->assertTrue($response->hasHeader('Access-Control-Expose-Headers'));
    }

    public function testAllowedAndExposedHeadersAreNotOverwritten()
    {
        $this->response_factory = HTTPFactoryBuilder::responseFactory();

        $request_handler_response = $this->response_factory->createResponse(200)
            ->withHeader('Access-Control-Allow-Headers', 'MyAllowedHeader')
            ->withHeader('Access-Control-Expose-Headers', 'MyExposedHeader');

        $request_handler = \Mockery::mock(RequestHandlerInterface::class);
        $request_handler->shouldReceive('handle')->andReturns($request_handler_response);

        $middleware = new TusCORSMiddleware();

        $response = $middleware->process(\Mockery::mock(ServerRequestInterface::class), $request_handler);

        $this->assertContains('MyAllowedHeader', $response->getHeader('Access-Control-Allow-Headers'));
        $this->assertContains('MyExposedHeader', $response->getHeader('Access-Control-Expose-Headers'));
    }
}
