<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Captcha;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Tuleap\Http\HTTPFactoryBuilder;

final class ClientTest extends TestCase
{
    public function testValidRequestIsAccepted(): void
    {
        $http_client    = new \Http\Mock\Client();
        $stream_factory = HTTPFactoryBuilder::streamFactory();
        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse()->withBody(
            $stream_factory->createStream('{"success": true}')
        ));

        $client = new Client('secret', $http_client, HTTPFactoryBuilder::requestFactory(), $stream_factory);

        $this->assertTrue($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testInvalidRequestIsRefused(): void
    {
        $http_client    = new \Http\Mock\Client();
        $stream_factory = HTTPFactoryBuilder::streamFactory();
        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse()->withBody(
            $stream_factory->createStream('{"success": false}')
        ));

        $client = new Client('secret', $http_client, HTTPFactoryBuilder::requestFactory(), $stream_factory);

        $this->assertFalse($client->verify('invalid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfThereIsANetworkFailure(): void
    {
        $http_client = new \Http\Mock\Client();
        $http_client->addException(
            new class extends Exception implements ClientExceptionInterface {
            }
        );

        $client = new Client('secret', $http_client, HTTPFactoryBuilder::requestFactory(), HTTPFactoryBuilder::streamFactory());

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfHTTPRequestFails(): void
    {
        $http_client = new \Http\Mock\Client();
        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse(500));

        $client = new Client('secret', $http_client, HTTPFactoryBuilder::requestFactory(), HTTPFactoryBuilder::streamFactory());

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfReceivedCanNotBeDecoded(): void
    {
        $http_client    = new \Http\Mock\Client();
        $stream_factory = HTTPFactoryBuilder::streamFactory();
        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse()->withBody(
            $stream_factory->createStream('')
        ));

        $client = new Client('secret', $http_client, HTTPFactoryBuilder::requestFactory(), $stream_factory);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfJSONObjectDoesNotContainSuccessKey(): void
    {
        $http_client    = new \Http\Mock\Client();
        $stream_factory = HTTPFactoryBuilder::streamFactory();
        $http_client->addResponse(HTTPFactoryBuilder::responseFactory()->createResponse()->withBody(
            $stream_factory->createStream('{"unexpected": true}')
        ));

        $client = new Client('secret', $http_client, HTTPFactoryBuilder::requestFactory(), $stream_factory);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }
}
