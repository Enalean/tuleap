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

namespace Tuleap\PrometheusMetrics;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\AlwaysSuccessfulRequestHandler;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;

/**
 * @covers \Tuleap\PrometheusMetrics\MetricsAuthentication
 */
final class MetricsAuthenticationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MetricsAuthentication $metrics_authentication;
    private RequestHandlerInterface $request_handler;
    private string $config_dir_root;

    public function setUp(): void
    {
        $response_factory      = HTTPFactoryBuilder::responseFactory();
        $this->request_handler = new AlwaysSuccessfulRequestHandler($response_factory);
        $this->config_dir_root = vfsStream::setup()->url();

        $this->metrics_authentication = new MetricsAuthentication($response_factory, new BasicAuthLoginExtractor(), $this->config_dir_root);
    }

    public function testAuthentication(): void
    {
        $expected_password = str_repeat('A', 16);
        file_put_contents($this->config_dir_root . '/metrics_secret.key', $expected_password);

        $response = $this->metrics_authentication->process(
            $this->getServerRequestWithBasicAuthorizationHeader('metrics', $expected_password),
            $this->request_handler
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testExecutionIsInterruptedWhenSecretFileIsNotPresent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->metrics_authentication->process(
            $this->getServerRequestWithBasicAuthorizationHeader('metrics', 'password'),
            $this->request_handler
        );
    }

    public function testExecutionIsInterruptedWhenSecretIsTooSmall(): void
    {
        file_put_contents($this->config_dir_root . '/metrics_secret.key', 'too_small');
        $this->expectException(\RuntimeException::class);
        $this->metrics_authentication->process(
            $this->getServerRequestWithBasicAuthorizationHeader('metrics', 'password'),
            $this->request_handler
        );
    }

    public function testNoBasicAuthorizationHeaderNotSet(): void
    {
        file_put_contents($this->config_dir_root . '/metrics_secret.key', str_repeat('A', 16));
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getHeaderLine')->with('Authorization')->willReturn('');
        $response = $this->metrics_authentication->process($server_request, $this->request_handler);

        self::assertEquals(401, $response->getStatusCode());
    }

    public function testAuthenticationRejectedWithIncorrectCredential(): void
    {
        file_put_contents($this->config_dir_root . '/metrics_secret.key', str_repeat('A', 16));

        $response = $this->metrics_authentication->process(
            $this->getServerRequestWithBasicAuthorizationHeader('wrong_username', 'wrong_password'),
            $this->request_handler
        );

        self::assertEquals(401, $response->getStatusCode());
    }

    private function getServerRequestWithBasicAuthorizationHeader(string $username, string $password): MockObject&ServerRequestInterface
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('Basic ' . base64_encode($username . ':' . $password));
        return $server_request;
    }
}
