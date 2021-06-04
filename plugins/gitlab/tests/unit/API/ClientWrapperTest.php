<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API;

use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

final class ClientWrapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RequestFactoryInterface
     */
    private $factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabHTTPClientFactory
     */
    private $client_factory;

    protected function setUp(): void
    {
        $this->factory        = $this->createMock(RequestFactoryInterface::class);
        $this->stream_factory = $this->createMock(StreamFactoryInterface::class);
        $this->client_factory = $this->createMock(GitlabHTTPClientFactory::class);

        $this->wrapper = new ClientWrapper($this->factory, $this->stream_factory, $this->client_factory);
    }

    /**
     * @testWith [200, "OK"]
     *           [204, "No Content"]
     */
    public function testDeleteUrl(int $status_code, string $reason): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

        $client_interface = $this->createMock(ClientInterface::class);
        $client           = new PluginClient($client_interface);

        $this->client_factory
            ->expects(self::once())
            ->method('buildHTTPClient')
            ->with($credentials)
            ->willReturn($client);

        $bare_request = $this->createMock(RequestInterface::class);

        $this->factory
            ->expects(self::once())
            ->method('createRequest')
            ->with('DELETE', 'https://gitlab.example.com/api/v4/url')
            ->willReturn($bare_request);

        $request = $this->createMock(RequestInterface::class);

        $bare_request
            ->expects(self::once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturn($request);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status_code);
        $response->method('getReasonPhrase')->willReturn($reason);

        $client_interface
            ->expects(self::once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $this->wrapper->deleteUrl($credentials, '/url');
    }

    /**
     * @testWith [100, "informal"]
     *           [300, "redirection"]
     *           [400, "client"]
     *           [500, "server"]
     */
    public function testDeleteUrlThrowsExceptionIfStatusCodeIsNotSuccess(int $status_code, string $reason): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

        $client_interface = $this->createMock(ClientInterface::class);
        $client           = new PluginClient($client_interface);

        $this->client_factory
            ->expects(self::once())
            ->method('buildHTTPClient')
            ->with($credentials)
            ->willReturn($client);

        $bare_request = $this->createMock(RequestInterface::class);

        $this->factory
            ->expects(self::once())
            ->method('createRequest')
            ->with('DELETE', 'https://gitlab.example.com/api/v4/url')
            ->willReturn($bare_request);

        $request = $this->createMock(RequestInterface::class);

        $bare_request
            ->expects(self::once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturn($request);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status_code);
        $response->method('getReasonPhrase')->willReturn($reason);

        $client_interface
            ->expects(self::once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $this->expectException(GitlabRequestException::class);

        $this->wrapper->deleteUrl($credentials, '/url');
    }

    public function testDeleteUrlThrowsExceptionIfClientCannotSendTheRequest(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

        $client_interface = $this->createMock(ClientInterface::class);
        $client           = new PluginClient($client_interface);

        $this->client_factory
            ->expects(self::once())
            ->method('buildHTTPClient')
            ->with($credentials)
            ->willReturn($client);

        $bare_request = $this->createMock(RequestInterface::class);

        $this->factory
            ->expects(self::once())
            ->method('createRequest')
            ->with('DELETE', 'https://gitlab.example.com/api/v4/url')
            ->willReturn($bare_request);

        $request = $this->createMock(RequestInterface::class);

        $bare_request
            ->expects(self::once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturn($request);

        $client_interface
            ->expects(self::once())
            ->method('sendRequest')
            ->with($request)
            ->willThrowException(new class extends \Exception implements ClientExceptionInterface {
            });

        $this->expectException(GitlabRequestException::class);

        $this->wrapper->deleteUrl($credentials, '/url');
    }
}
