<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

namespace Tuleap\Webhook;

use Http\Client\Exception\HttpException;
use Http\Mock\Client;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class EmitterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWebhooksAreEmitted(): void
    {
        $request_factory = \Mockery::mock(RequestFactoryInterface::class);
        $stream_factory  = \Mockery::mock(StreamFactoryInterface::class);
        $http_client     = new Client();
        $status_logger   = \Mockery::mock(StatusLogger::class);

        $webhook_emitter = new Emitter($request_factory, $stream_factory, $http_client, $status_logger);

        $webhook_1 = \Mockery::mock(Webhook::class);
        $webhook_1->shouldReceive('getUrl');
        $webhook_2 = \Mockery::mock(Webhook::class);
        $webhook_2->shouldReceive('getUrl');
        $payload   = \Mockery::mock(Payload::class);
        $payload->shouldReceive('getPayload');

        $http_client->addException(\Mockery::mock(HttpException::class));
        $http_response = \Mockery::mock(ResponseInterface::class);
        $http_response->shouldReceive('getStatusCode');
        $http_response->shouldReceive('getReasonPhrase');
        $http_client->addResponse($http_response);

        $request = \Mockery::mock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturnSelf();
        $request->shouldReceive('withBody')->andReturnSelf();
        $request_factory->shouldReceive('createRequest')->twice()
            ->andReturns($request);
        $stream_factory->shouldReceive('createStream')->andReturn(\Mockery::mock(StreamInterface::class));
        $status_logger->shouldReceive('log')->twice();

        $webhook_emitter->emit($payload, $webhook_1, $webhook_2);
    }
}
