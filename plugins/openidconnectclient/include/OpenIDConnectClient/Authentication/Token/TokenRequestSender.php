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

class TokenRequestSender
{
    /**
     * @var \Http\Client\HttpClient
     */
    private $http_client;

    public function __construct(\Http\Client\HttpClient $http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * @return TokenResponse
     * @throws IncorrectTokenResponseTypeException
     * @throws IncorrectlyFormattedTokenResponseException
     * @throws \Http\Client\Exception
     */
    public function sendTokenRequest(TokenRequest $request)
    {
        $response = $this->http_client->sendRequest($request->getHTTPRequest());
        return TokenResponse::buildFromHTTPResponse($response);
    }
}
