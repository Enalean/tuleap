<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Uri;

use InoOicClient\Oic\Authorization\Request;

class Generator
{
    /**
     * @return string
     */
    public function createAuthorizationRequestUri(Request $request)
    {
        $client_info = $request->getClientInfo();

        $params = array(
            'client_id'     => $client_info->getClientId(),
            'redirect_uri'  => $client_info->getRedirectUri(),
            'response_type' => $this->transformArrayToSpaceDelimitedString($request->getResponseType()),
            'scope'         => $this->transformArrayToSpaceDelimitedString($request->getScope()),
            'state'         => $request->getState(),
            'nonce'         => $request->getNonce()
        );

        return $client_info->getAuthorizationEndpoint() . '?' . http_build_query($params);
    }

    /**
     * @return string
     */
    private function transformArrayToSpaceDelimitedString(array $list)
    {
        return implode(' ', $list);
    }
}
