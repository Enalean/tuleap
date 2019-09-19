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

namespace Tuleap\OpenIDConnectClient\Login;

class ConnectorPresenter
{

    private $providers_authorization_request_uri;

    public function __construct(array $providers_authorization_request_uri)
    {
        $this->providers_authorization_request_uri = $providers_authorization_request_uri;
    }

    /**
     * @return string
     */
    public function or_label()
    {
        return dgettext('tuleap-openidconnectclient', 'or login with');
    }

    public function are_there_providers()
    {
        return count($this->providers_authorization_request_uri) > 0;
    }

    /**
     * @return array
     */
    public function providers()
    {
        return $this->providers_authorization_request_uri;
    }
}
