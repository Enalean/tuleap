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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

final class ClientWrapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ClientWrapper
     */
    private $wrapper;
    /**
     * @var LegacyMockInterface|MockInterface|RequestFactoryInterface
     */
    private $factory;
    /**
     * @var LegacyMockInterface|MockInterface|StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var LegacyMockInterface|MockInterface|GitlabHTTPClientFactory
     */
    private $client_factory;

    protected function setUp(): void
    {
        $this->factory        = Mockery::mock(RequestFactoryInterface::class);
        $this->stream_factory = Mockery::mock(StreamFactoryInterface::class);
        $this->client_factory = Mockery::mock(GitlabHTTPClientFactory::class);

        $this->wrapper = new ClientWrapper($this->factory, $this->stream_factory, $this->client_factory);
    }

    /**
     * @testWith [200, "OK"]
     *           [204, "No Content"]
     */
    public function testDeleteUrl(int $status_code, string $reason): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

        $client_interface = Mockery::mock(ClientInterface::class);
        $client           = new PluginClient($client_interface);

        $this->client_factory
            ->shouldReceive('buildHTTPClient')
            ->with($credentials)
            ->andReturn($client);

        $bare_request = Mockery::mock(RequestInterface::class);

        $this->factory
            ->shouldReceive('createRequest')
            ->with('DELETE', 'https://gitlab.example.com/api/v4/url')
            ->andReturn($bare_request);

        $request = Mockery::mock(RequestInterface::class);

        $bare_request
            ->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->andReturn($request);

        $response = Mockery::mock(ResponseInterface::class, [
            'getStatusCode'   => $status_code,
            'getReasonPhrase' => $reason,
        ]);

        $client_interface
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);

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

        $client_interface = Mockery::mock(ClientInterface::class);
        $client           = new PluginClient($client_interface);

        $this->client_factory
            ->shouldReceive('buildHTTPClient')
            ->with($credentials)
            ->andReturn($client);

        $bare_request = Mockery::mock(RequestInterface::class);

        $this->factory
            ->shouldReceive('createRequest')
            ->with('DELETE', 'https://gitlab.example.com/api/v4/url')
            ->andReturn($bare_request);

        $request = Mockery::mock(RequestInterface::class);

        $bare_request
            ->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->andReturn($request);

        $response = Mockery::mock(ResponseInterface::class, [
            'getStatusCode'   => $status_code,
            'getReasonPhrase' => $reason,
        ]);

        $client_interface
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andReturn($response);

        $this->expectException(GitlabRequestException::class);

        $this->wrapper->deleteUrl($credentials, '/url');
    }

    public function testDeleteUrlThrowsExceptionIfClientCannotSendTheRequest(): void
    {
        $credentials = CredentialsTestBuilder::get()->build();

        $client_interface = Mockery::mock(ClientInterface::class);
        $client           = new PluginClient($client_interface);

        $this->client_factory
            ->shouldReceive('buildHTTPClient')
            ->with($credentials)
            ->andReturn($client);

        $bare_request = Mockery::mock(RequestInterface::class);

        $this->factory
            ->shouldReceive('createRequest')
            ->with('DELETE', 'https://gitlab.example.com/api/v4/url')
            ->andReturn($bare_request);

        $request = Mockery::mock(RequestInterface::class);

        $bare_request
            ->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->andReturn($request);

        $client_interface
            ->shouldReceive('sendRequest')
            ->with($request)
            ->andThrow(new class extends \Exception implements ClientExceptionInterface {
            });

        $this->expectException(GitlabRequestException::class);

        $this->wrapper->deleteUrl($credentials, '/url');
    }
}
