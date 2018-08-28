<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Token;

use Http\Message\RequestFactory;
use InoOicClient\Oic\Authorization\Response as AuthorizationResponse;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class TokenRequestCreator
{
    /**
     * @var RequestFactory
     */
    private $http_request_factory;

    public function __construct(RequestFactory $http_request_factory)
    {
        $this->http_request_factory = $http_request_factory;
    }

    /**
     * @return TokenRequest
     */
    public function createTokenRequest(Provider $provider, AuthorizationResponse $authorization_response, $redirect_uri)
    {
        $http_request = $this->http_request_factory->createRequest(
            'POST',
            $provider->getTokenEndpoint(),
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json'
            ],
            http_build_query(
                [
                    'grant_type'    => 'authorization_code',
                    'code'          => $authorization_response->getCode(),
                    'redirect_uri'  => $redirect_uri,
                    'client_id'     => $provider->getClientId(),
                    'client_secret' => $provider->getClientSecret(),
                ]
            )
        );

        return new TokenRequest($http_request);
    }
}
