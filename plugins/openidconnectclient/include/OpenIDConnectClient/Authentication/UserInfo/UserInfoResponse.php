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

namespace Tuleap\OpenIDConnectClient\Authentication\UserInfo;

use Psr\Http\Message\ResponseInterface;

class UserInfoResponse
{
    /**
     * @var array
     */
    private $claims;

    private function __construct(array $claims)
    {
        $this->claims = $claims;
    }

    /**
     * @return self
     */
    public static function buildEmptyUserInfoResponse()
    {
        return new self([]);
    }

    /**
     * @return UserInfoResponse
     * @throws NotSupportedContentTypeUserInfoResponseException
     * @throws IncorrectlyFormattedUserInfoResponseException
     */
    public static function buildFromHTTPResponse(ResponseInterface $response)
    {
        $content_type = $response->getHeaderLine('content-type');
        if (stripos($content_type, 'application/json') === false) {
            throw new NotSupportedContentTypeUserInfoResponseException($content_type);
        }
        $user_info_response_body = (string) $response->getBody();
        $json_response           = json_decode($user_info_response_body, true);
        if ($json_response === null || ! isset($json_response['sub'])) {
            throw new IncorrectlyFormattedUserInfoResponseException($user_info_response_body);
        }

        return new self((array) $json_response);
    }

    /**
     * @return array
     */
    public function getClaims()
    {
        return $this->claims;
    }
}
