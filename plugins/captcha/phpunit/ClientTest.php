<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testValidRequestIsAccepted()
    {
        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('getLastResponse')->andReturns('{"success": true}');
        $http_client->shouldReceive('addOptions');
        $http_client->shouldReceive('doRequest');

        $client = new Client('secret', $http_client);

        $this->assertTrue($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testInvalidRequestIsRefused()
    {
        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('getLastResponse')->andReturns('{"success": false}');
        $http_client->shouldReceive('addOptions');
        $http_client->shouldReceive('doRequest');

        $client = new Client('wrong_secret', $http_client);

        $this->assertFalse($client->verify('invalid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfHTTPRequestFails()
    {
        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('addOptions');
        $http_client->shouldReceive('doRequest')->andThrow(\Mockery::mock(\Http_ClientException::class));

        $client = new Client('secret', $http_client);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfReceivedCanNotBeDecoded()
    {
        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('getLastResponse')->andReturns('');
        $http_client->shouldReceive('addOptions');
        $http_client->shouldReceive('doRequest');

        $client = new Client('secret', $http_client);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function testValidationIsRefusedIfJSONObjectDoesNotContainSuccessKey()
    {
        $http_client = \Mockery::mock(\Http_Client::class);
        $http_client->shouldReceive('getLastResponse')->andReturns('{"unexpected": true}');
        $http_client->shouldReceive('addOptions');
        $http_client->shouldReceive('doRequest');

        $client = new Client('secret', $http_client);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }
}
