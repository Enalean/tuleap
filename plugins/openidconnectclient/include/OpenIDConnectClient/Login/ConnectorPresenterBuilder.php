<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequestCreator;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class ConnectorPresenterBuilder
{
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var AuthorizationRequestCreator
     */
    private $authorization_request_creator;

    public function __construct(ProviderManager $provider_manager, AuthorizationRequestCreator $authorization_request_creator)
    {
        $this->provider_manager              = $provider_manager;
        $this->authorization_request_creator = $authorization_request_creator;
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

    /**
     * @return array
     */
    private function getProvidersWithRequestUri($return_to)
    {
        $providers                           = $this->provider_manager->getProvidersUsableToLogIn();
        $providers_authorization_request_uri = array();
        foreach ($providers as $provider) {
            $authorization_request = $this->authorization_request_creator->createAuthorizationRequest($provider, $return_to);

            $providers_authorization_request_uri[] = array(
                'name'                      => $provider->getName(),
                'icon'                      => $provider->getIcon(),
                'color'                     => $provider->getColor(),
                'authorization_request_uri' => $authorization_request->getURL()
            );
        }

        return $providers_authorization_request_uri;
    }
}
