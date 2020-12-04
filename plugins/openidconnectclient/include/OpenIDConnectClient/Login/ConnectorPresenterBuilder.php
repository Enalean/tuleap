<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class ConnectorPresenterBuilder
{
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var LoginURLGenerator
     */
    private $login_url_generator;

    public function __construct(ProviderManager $provider_manager, LoginURLGenerator $login_url_generator)
    {
        $this->provider_manager    = $provider_manager;
        $this->login_url_generator = $login_url_generator;
    }

    /**
     * @return ConnectorPresenter
     */
    public function getLoginConnectorPresenter($return_to)
    {
        $providers_authorization_request_uri = $this->getProvidersWithRequestUri($return_to);
        return new ConnectorPresenter($providers_authorization_request_uri);
    }

    /**
     * @return SpecificLoginPresenter
     */
    public function getLoginSpecificPageConnectorPresenter($return_to)
    {
        $providers_authorization_request_uri = $this->getProvidersWithRequestUri($return_to);
        return new SpecificLoginPresenter($providers_authorization_request_uri);
    }

    private function getProvidersWithRequestUri(?string $return_to): array
    {
        $providers                   = $this->provider_manager->getProvidersUsableToLogIn();
        $providers_login_request_uri = [];
        foreach ($providers as $provider) {
            $providers_login_request_uri[] = [
                'name'                      => $provider->getName(),
                'icon'                      => $provider->getIcon(),
                'color'                     => $provider->getColor(),
                'login_request_uri'         => $this->login_url_generator->getLoginURL($provider, $return_to),
            ];
        }

        return $providers_login_request_uri;
    }
}
