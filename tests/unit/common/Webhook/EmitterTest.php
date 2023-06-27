<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class EmitterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testWebhooksAreEmitted(): void
    {
        $request_factory = $this->createMock(RequestFactoryInterface::class);
        $stream_factory  = $this->createMock(StreamFactoryInterface::class);
        $http_client     = new Client();
        $status_logger   = $this->createMock(StatusLogger::class);

        $webhook_emitter = new Emitter($request_factory, $stream_factory, $http_client, $status_logger);

        $webhook_1 = $this->createMock(Webhook::class);
        $webhook_1->method('getUrl');
        $webhook_2 = $this->createMock(Webhook::class);
        $webhook_2->method('getUrl');
        $payload = $this->createMock(Payload::class);
        $payload->method('getPayload');

        $http_client->addException($this->createMock(HttpException::class));
        $http_response = $this->createMock(ResponseInterface::class);
        $http_response->method('getStatusCode');
        $http_response->method('getReasonPhrase');
        $http_client->addResponse($http_response);

        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $request_factory->expects(self::exactly(2))->method('createRequest')
            ->willReturn($request);
        $stream_factory->method('createStream')->willReturn($this->createMock(StreamInterface::class));
        $status_logger->expects(self::exactly(2))->method('log');

        $webhook_emitter->emit($payload, $webhook_1, $webhook_2);
    }

    public function testLogsNetworkException(): void
    {
        $http_client   = new Client();
        $status_logger = new class implements StatusLogger {
            public bool $does_something_has_been_logged = false;
            public function log(Webhook $webhook, $status): void
            {
                $this->does_something_has_been_logged = true;
            }
        };

        $http_client->addException(
            new class extends \RuntimeException implements \Http\Client\Exception, \Psr\Http\Client\NetworkExceptionInterface {
                public function getRequest(): RequestInterface
                {
                    return HTTPFactoryBuilder::requestFactory()->createRequest('POST', 'https://example.com/some_url');
                }
            }
        );

        $webhook_emitter = new Emitter(HTTPFactoryBuilder::requestFactory(), HTTPFactoryBuilder::streamFactory(), $http_client, $status_logger);

        $webhook_emitter->emit(
            self::buildPayload(),
            self::buildWebhook()
        );

        self::assertCount(1, $http_client->getRequests());
    }

    private static function buildPayload(): Payload
    {
        return new class implements Payload {
            public function getPayload(): array
            {
                return [];
            }
        };
    }

    private static function buildWebhook(): Webhook
    {
        return new class implements Webhook {
            public function getId(): int
            {
                return 1;
            }

            public function getUrl(): string
            {
                return 'https://example.com/some_url';
            }
        };
    }
}
