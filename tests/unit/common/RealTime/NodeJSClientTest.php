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

namespace Tuleap\RealTime;

use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;

final class NodeJSClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testMessageIsTransmittedToRealTimeNodeJSServer(): void
    {
        $realtime_server_address = 'realtime.example.com';
        ForgeConfig::set('nodejs_server', $realtime_server_address);
        ForgeConfig::set('nodejs_server_int', '');

        $http_client   = new \Http\Mock\Client();
        $logger        = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $nodejs_client = new NodeJSClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger
        );

        $logger->shouldNotReceive('error');

        $nodejs_client->sendMessage(
            new MessageDataPresenter(
                '101',
                'uuid',
                '1',
                Mockery::mock(MessageRightsPresenter::class),
                'cmd',
                'data'
            )
        );

        $requests = $http_client->getRequests();
        $this->assertCount(1, $requests);
        $request  = $requests[0];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringStartsWith('https://' . $realtime_server_address, (string) $request->getUri());
    }
}
