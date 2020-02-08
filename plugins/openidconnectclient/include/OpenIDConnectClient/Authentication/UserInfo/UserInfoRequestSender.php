<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\UserInfo;

use Psr\Http\Client\ClientInterface;

class UserInfoRequestSender
{
    /**
     * @var ClientInterface
     */
    private $http_client;

    public function __construct(ClientInterface $http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * @return UserInfoResponse
     * @throws NotSupportedContentTypeUserInfoResponseException
     * @throws IncorrectlyFormattedUserInfoResponseException
     * @throws \Http\Client\Exception
     */
    public function sendUserInfoRequest(UserInfoRequest $request)
    {
        $http_request = $request->getHTTPRequest();
        if ($http_request === null) {
            return UserInfoResponse::buildEmptyUserInfoResponse();
        }

        $response = $this->http_client->sendRequest($request->getHTTPRequest());
        return UserInfoResponse::buildFromHTTPResponse($response);
    }
}
