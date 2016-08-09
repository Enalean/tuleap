<?php

/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Uri;

use InoOicClient\Client\ClientInfo;
use InoOicClient\Oic\Authorization\Request;

require_once(__DIR__ . '/../../bootstrap.php');

class GeneratorTest extends \TuleapTestCase
{
    /**
     * @var ClientInfo
     */
    private $client_info;

    /**
     * @var Request
     */
    private $request;

    public function setUp()
    {
        parent::setUp();
        $this->client_info = new ClientInfo();
        $this->client_info->setClientId('id1');
        $this->client_info->setRedirectUri('https://tuleap.example.com/plugins/openidconnectclient/');
        $this->client_info->setAuthorizationEndpoint('https://provider.example.com/');

        $this->request = new Request($this->client_info, 'code', 'openid', 'random_state');
        $this->request->setNonce('random_nonce');
    }

    public function itGeneratesAnUriUsingTheAuthorizationEndpoint()
    {
        $generator = new Generator();
        $authorization_uri = $generator->createAuthorizationRequestUri($this->request);

        $this->assertStringBeginsWith($authorization_uri, 'https://provider.example.com/');
    }

    public function itGeneratesAnURIWithAllTheExpectedParameters()
    {
        $generator = new Generator();
        $authorization_uri = $generator->createAuthorizationRequestUri($this->request);

        $this->assertStringContains($authorization_uri, 'client_id=');
        $this->assertStringContains($authorization_uri, 'redirect_uri=');
        $this->assertStringContains($authorization_uri, 'response_type=');
        $this->assertStringContains($authorization_uri, 'scope=');
        $this->assertStringContains($authorization_uri, 'state=');
        $this->assertStringContains($authorization_uri, 'nonce=');
    }

    public function itProperlyTransformsParameters()
    {
        $this->request->setScope(array('openid', 'profile'));
        $this->request->setResponseType(array('code', 'id_token'));

        $generator = new Generator();
        $authorization_uri = $generator->createAuthorizationRequestUri($this->request);

        $this->assertTrue(strpos($authorization_uri, 'scope=openid+profile') !== false);
        $this->assertTrue(strpos($authorization_uri, 'response_type=code+id_token') !== false);
    }
}
