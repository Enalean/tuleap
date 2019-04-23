<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use Git_Driver_GerritREST;
use Git_RemoteServer_GerritServer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GerritRESTTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDetectsDeleteProjectPluginForGerritLesserThan214()
    {
        $logger = \Mockery::mock(\Logger::class);
        $logger->shouldReceive('info');
        $logger->shouldReceive('error')->never();

        $response = \Mockery::mock(\Guzzle\Http\Message\Response::class);
        $response->shouldReceive('getBody')->andReturn(")]}'\n" . json_encode(['deleteproject' => 1]));

        $request = \Mockery::mock(\Guzzle\Http\Message\RequestInterface::class);
        $request->shouldReceive('setAuth');
        $request->shouldReceive('send')->andReturn($response);

        $guzzle_client = \Mockery::mock(\Guzzle\Http\Client::class);
        $guzzle_client->shouldReceive('get')->andReturn($request);

        $server = \Mockery::mock(Git_RemoteServer_GerritServer::class);
        $server->shouldReceive(
            [
                'getBaseUrl'      => 'https://url.test',
                'getLogin'        => 'gerrit-admin',
                'getHTTPPassword' => '1337'
            ]
        );

        $driver = new Git_Driver_GerritREST($guzzle_client, $logger, 'Digest');
        $this->assertTrue($driver->isDeletePluginEnabled($server));
    }

    public function testItDetectsDeleteProjectPluginForGerritGreaterThan214()
    {
        $logger = \Mockery::mock(\Logger::class);
        $logger->shouldReceive('info');
        $logger->shouldReceive('error')->never();

        $response = \Mockery::mock(\Guzzle\Http\Message\Response::class);
        $response->shouldReceive('getBody')->andReturn(")]}'\n" . json_encode(['delete-project' => 1]));

        $request = \Mockery::mock(\Guzzle\Http\Message\RequestInterface::class);
        $request->shouldReceive('setAuth');
        $request->shouldReceive('send')->andReturn($response);

        $guzzle_client = \Mockery::mock(\Guzzle\Http\Client::class);
        $guzzle_client->shouldReceive('get')->andReturn($request);

        $server = \Mockery::mock(Git_RemoteServer_GerritServer::class);
        $server->shouldReceive(
            [
                'getBaseUrl'      => 'https://url.test',
                'getLogin'        => 'gerrit-admin',
                'getHTTPPassword' => '1337'
            ]
        );

        $driver = new Git_Driver_GerritREST($guzzle_client, $logger, 'Digest');
        $this->assertTrue($driver->isDeletePluginEnabled($server));
    }

    public function testItDetectsAbsenceOfDeleteProjectPlugin()
    {
        $logger = \Mockery::mock(\Logger::class);
        $logger->shouldReceive('info');
        $logger->shouldReceive('error')->never();

        $response = \Mockery::mock(\Guzzle\Http\Message\Response::class);
        $response->shouldReceive('getBody')->andReturn(")]}'\n" . json_encode(['replication' => 1]));

        $request = \Mockery::mock(\Guzzle\Http\Message\RequestInterface::class);
        $request->shouldReceive('setAuth');
        $request->shouldReceive('send')->andReturn($response);

        $guzzle_client = \Mockery::mock(\Guzzle\Http\Client::class);
        $guzzle_client->shouldReceive('get')->andReturn($request);

        $server = \Mockery::mock(Git_RemoteServer_GerritServer::class);
        $server->shouldReceive(
            [
                'getBaseUrl'      => 'https://url.test',
                'getLogin'        => 'gerrit-admin',
                'getHTTPPassword' => '1337'
            ]
        );

        $driver = new Git_Driver_GerritREST($guzzle_client, $logger, 'Digest');
        $this->assertFalse($driver->isDeletePluginEnabled($server));
    }
}
