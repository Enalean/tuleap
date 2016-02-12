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

use Tuleap\OpenIDConnectClient\Authentication\AuthorizationDispatcher;
use Tuleap\OpenIDConnectClient\Authentication\Flow;


require_once(__DIR__ . '/../bootstrap.php');
class FlowTest extends TuleapTestCase {

    public function itCreatesValidAuthorizationRequest() {
        $authorization_endpoint = 'https://endpoint.example.com';
        $signed_state           = 'Tuleap_signed_state';

        $provider_manager = mock('Tuleap\OpenIDConnectClient\Provider\ProviderManager');
        $provider         = mock('Tuleap\OpenIDConnectClient\Provider\Provider');
        $provider->setReturnValue('getAuthorizationEndpoint', $authorization_endpoint);
        $provider->setReturnValue('getClientId', '1234');
        $state            = mock('Tuleap\OpenIDConnectClient\Authentication\State');
        $state->setReturnValue('getSignedState', $signed_state);
        $state_manager    = mock('Tuleap\OpenIDConnectClient\Authentication\StateManager');
        $state_manager->setReturnValue('initState', $state);

        $flow             = new Flow(
            $state_manager,
            new AuthorizationDispatcher($state_manager),
            $provider_manager
        );

        $request_uri = $flow->getAuthorizationRequestUri($provider);
        $this->assertTrue(strpos($request_uri, $authorization_endpoint) === 0);
        $this->assertTrue(strpos($request_uri, $signed_state) !== false);
    }
}