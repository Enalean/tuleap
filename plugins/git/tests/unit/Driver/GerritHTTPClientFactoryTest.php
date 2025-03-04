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

namespace Tuleap\Git\Driver;

use Git_RemoteServer_GerritServer;
use Http\Mock\Client;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GerritHTTPClientFactoryTest extends TestCase
{
    public function testHTTPClientSetBasicAuthHeaderToRequest(): void
    {
        $mock_client = new Client();
        $factory     = new GerritHTTPClientFactory($mock_client);

        $gerrit_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_server->method('getLogin')->willReturn('username');
        $gerrit_server->method('getHTTPPassword')->willReturn('password');
        $gerrit_http_client = $factory->buildHTTPClient($gerrit_server);

        $request = HTTPFactoryBuilder::requestFactory()->createRequest('GET', 'https://gerrit.example.com/');
        $gerrit_http_client->sendRequest($request);

        $sent_requests = $mock_client->getRequests();
        self::assertCount(1, $sent_requests);
        $authorization_header_line = $sent_requests[0]->getHeaderLine('Authorization');
        self::assertStringStartsWith('Basic ', $authorization_header_line);
    }
}
