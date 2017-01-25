<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once __DIR__ . '/bootstrap.php';

class ClientTest extends \TuleapTestCase
{
    public function itAcceptsValidRequest()
    {
        $http_client = mock('Http_Client');
        stub($http_client)->getLastResponse()->returns('{"success": true}');

        $client = new Client('secret', $http_client);

        $this->assertTrue($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function itRefusesInvalidRequest()
    {
        $http_client = mock('Http_Client');
        stub($http_client)->getLastResponse()->returns('{"success": false}');

        $client = new Client('wrong_secret', $http_client);

        $this->assertFalse($client->verify('invalid_challenge', '192.0.2.1'));
    }

    public function itRefusesValidationIfHttpRequestFail()
    {
        $http_client = mock('Http_Client');
        stub($http_client)->doRequest()->throws(new \Http_ClientException());

        $client = new Client('secret', $http_client);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function itRefusesValidationIfReceivedJSONCanNotBeDecoded()
    {
        $http_client = mock('Http_Client');
        stub($http_client)->getLastResponse()->returns('');

        $client = new Client('secret', $http_client);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }

    public function itRefusesValidationIfReceivedJSONObjectDoesNotContainSuccessAttribute()
    {
        $http_client = mock('Http_Client');
        stub($http_client)->getLastResponse()->returns('{"unexpected": true}');

        $client = new Client('secret', $http_client);

        $this->assertFalse($client->verify('valid_challenge', '192.0.2.1'));
    }
}
