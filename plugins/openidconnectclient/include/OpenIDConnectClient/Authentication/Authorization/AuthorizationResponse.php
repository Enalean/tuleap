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

namespace Tuleap\OpenIDConnectClient\Authentication\Authorization;

class AuthorizationResponse
{
    /**
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    private $state;

    private function __construct($code, $state)
    {
        $this->code  = $code;
        $this->state = $state;
    }

    /**
     * @return AuthorizationResponse
     */
    public static function buildFromHTTPRequest(\HTTPRequest $request)
    {
        $code  = self::getParameterFromRequest($request, 'code');
        $state = self::getParameterFromRequest($request, 'state');

        return new self($code, $state);
    }

    /**
     * @return string
     */
    private static function getParameterFromRequest(\HTTPRequest $request, $parameter)
    {
        $value = $request->get($parameter);
        if ($value === false) {
            throw new MissingParameterAuthorizationResponseException($parameter);
        }
        return $value;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
