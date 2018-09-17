<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login;

use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequestCreator;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class LoginUniqueAuthenticationUrlGenerator
{
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var AuthorizationRequestCreator
     */
    private $authorization_request_creator;

    public function __construct(ProviderManager $provider_manager, AuthorizationRequestCreator $authorization_request_creator)
    {
        $this->provider_manager              = $provider_manager;
        $this->authorization_request_creator = $authorization_request_creator;
    }

    public function getURL($return_to)
    {
        $providers = $this->provider_manager->getProvidersUsableToLogIn();
        if (count($providers) !== 1) {
            throw new IncoherentDataUniqueProviderException();
        }

        $unique_authentication_provider = $providers[0];
        $authorization_request          = $this->authorization_request_creator->createAuthorizationRequest(
            $unique_authentication_provider,
            $return_to
        );
        return $authorization_request->getURL();
    }
}
