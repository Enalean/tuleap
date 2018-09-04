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

namespace Tuleap\OpenIDConnectClient\Authentication\UserInfo;

use Http\Message\RequestFactory;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenResponse;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class UserInfoRequestCreator
{
    /**
     * @var RequestFactory
     */
    private $http_request_factory;

    public function __construct(RequestFactory $http_request_factory)
    {
        $this->http_request_factory = $http_request_factory;
    }

    public function createUserInfoRequest(Provider $provider, TokenResponse $token_response)
    {
        if ($provider->getUserInfoEndpoint() === '') {
            return new EmptyUserInfoRequest();
        }

        $http_request = $this->http_request_factory->createRequest(
            'GET',
            $provider->getUserInfoEndpoint(),
            [
                'Authorization' => 'Bearer ' . $token_response->getAccessToken()
            ]
        );
        return new UserInfoRequestWithData($http_request);
    }
}
