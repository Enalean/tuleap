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

use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class ConnectorPresenterBuilder {
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var Flow
     */
    private $flow;

    public function __construct(ProviderManager $provider_manager, Flow $flow) {
        $this->provider_manager = $provider_manager;
        $this->flow             = $flow;
    }

    /**
     * @return ConnectorPresenter
     */
    public function getLoginConnectorPresenter($return_to) {
        $providers                           = $this->provider_manager->getProvidersUsableToLogIn();
        $providers_authorization_request_uri = array();
        foreach($providers as $provider) {
            $providers_authorization_request_uri[] = array(
                'name'                      => $provider->getName(),
                'icon'                      => $provider->getIcon(),
                'color'                     => $provider->getColor(),
                'authorization_request_uri' => $this->flow->getAuthorizationRequestUri($provider, $return_to)
            );
        }

        return new ConnectorPresenter($providers_authorization_request_uri);
    }
}
