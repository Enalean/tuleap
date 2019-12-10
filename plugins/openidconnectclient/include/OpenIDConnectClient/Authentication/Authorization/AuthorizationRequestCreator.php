<?php
/**
 * Copyright (c) Enalean, 2016-present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Authorization;

use ForgeConfig;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class AuthorizationRequestCreator
{
    /**
     * @var StateManager
     */
    private $state_manager;

    public function __construct(StateManager $state_manager)
    {
        $this->state_manager        = $state_manager;
    }

    /**
     * @return AuthorizationRequest
     */
    public function createAuthorizationRequest(Provider $provider, $return_to)
    {
        $state = $this->state_manager->initState($provider, $return_to);

        $url = $provider->getAuthorizationEndpoint() . '?' . http_build_query([
                'client_id'     => $provider->getClientId(),
                'redirect_uri'  => $provider->getRedirectUri(),
                'response_type' => 'code',
                'scope'         => $this->getScope($provider),
                'state'         => $state->getSignedState(),
                'nonce'         => $state->getNonce()
            ]);

        return new AuthorizationRequest($url);
    }

    /**
     * @return string
     */
    private function getScope(Provider $provider)
    {
        if ($provider->isUniqueAuthenticationEndpoint()) {
            return 'openid profile email';
        }

        return 'openid';
    }
}
