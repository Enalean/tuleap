<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Client\Authentication\BasicAuth;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationResponse;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class TokenRequestCreator
{
    /**
     * @var RequestFactoryInterface
     */
    private $http_request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var BasicAuth
     */
    private $basic_auth;

    public function __construct(
        RequestFactoryInterface $http_request_factory,
        StreamFactoryInterface $stream_factory,
        BasicAuth $basic_auth
    ) {
        $this->http_request_factory = $http_request_factory;
        $this->stream_factory       = $stream_factory;
        $this->basic_auth           = $basic_auth;
    }

    public function createTokenRequest(Provider $provider, AuthorizationResponse $authorization_response, string $redirect_uri, ConcealedString $pkce_code_verifier): TokenRequest
    {
        $http_request = $this->http_request_factory->createRequest(
            'POST',
            $provider->getTokenEndpoint()
        );
        $http_request = $http_request
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody(
                $this->stream_factory->createStream(
                    http_build_query(
                        [
                            'grant_type'    => 'authorization_code',
                            'code'          => $authorization_response->getCode(),
                            'redirect_uri'  => $redirect_uri,
                            'code_verifier' => $pkce_code_verifier->getString(),
                        ]
                    )
                )
            );
        $http_request = $this->basic_auth->authenticate(
            $http_request,
            $provider->getClientId(),
            new ConcealedString($provider->getClientSecret())
        );

        return new TokenRequest($http_request);
    }
}
