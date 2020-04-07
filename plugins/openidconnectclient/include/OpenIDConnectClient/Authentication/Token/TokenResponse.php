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

use Psr\Http\Message\ResponseInterface;

class TokenResponse
{
    /**
     * @var string
     */
    private $id_token;
    /**
     * @var string
     */
    private $access_token;

    private function __construct($id_token, $access_token)
    {
        $this->id_token     = $id_token;
        $this->access_token = $access_token;
    }

    /**
     * @return self
     * @throws IncorrectTokenResponseTypeException
     * @throws IncorrectlyFormattedTokenResponseException
     */
    public static function buildFromHTTPResponse(ResponseInterface $response)
    {
        $token_response_body = (string) $response->getBody();
        $json_response       = json_decode($token_response_body, true);
        if (
            $json_response === null ||
            ! isset($json_response['token_type'], $json_response['id_token'], $json_response['access_token'])
        ) {
            throw new IncorrectlyFormattedTokenResponseException($token_response_body);
        }

        if (strtolower($json_response['token_type']) !== 'bearer') {
            throw new IncorrectTokenResponseTypeException($json_response['token_type']);
        }

        return new self($json_response['id_token'], $json_response['access_token']);
    }

    /**
     * @return string
     */
    public function getIDToken()
    {
        return $this->id_token;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }
}
