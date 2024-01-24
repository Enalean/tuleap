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

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;

final class NodeJSClientTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testMessageIsTransmittedToRealTimeNodeJSServer(): void
    {
        $http_client   = new \Http\Mock\Client();
        $logger        = new TestLogger();
        $nodejs_client = new NodeJSClient(
            $http_client,
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $logger
        );

        $nodejs_client->sendMessage(
            new MessageDataPresenter(
                '101',
                'uuid',
                '1',
                $this->createMock(MessageRightsPresenter::class),
                'cmd',
                'data'
            )
        );

        $requests = $http_client->getRequests();
        self::assertCount(1, $requests);
        $request = $requests[0];
        self::assertEquals('POST', $request->getMethod());
        self::assertStringStartsWith('http://localhost:2999', (string) $request->getUri());
        self::assertFalse($logger->hasErrorRecords());
    }
}
