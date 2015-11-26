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

namespace Tuleap\OpenIDConnectClient;

use InoOicClient\Flow\Basic;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use ForgeConfig;

class Flow extends Basic {

    public function __construct(Provider $provider) {
        $configuration = $this->generateConfiguration($provider);
        parent::__construct($configuration);
    }

    /**
     * @return array
     */
    private function generateConfiguration(Provider $provider) {
        return array(
            'client_info' => array(
                'client_id'    => $provider->getClientId(),
                'redirect_uri' => $this->getRedirectUri(),

                'authorization_endpoint' => $provider->getAuthorizationEndpoint(),
                'token_endpoint'         => $provider->getTokenEndpoint(),
                'user_info_endpoint'     => $provider->getUserInfoEndpoint(),

                'authentication_info' => array(
                    'method' => 'client_secret_post',
                    'params' => array(
                        'client_secret' => $provider->getClientSecret()
                    )
                )
            ),
            'token_dispatcher' => array(
                'http_options' => array(
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            )
        );
    }

    /**
     * @return string
     */
    private function getRedirectUri() {
        return 'https://'. ForgeConfig::get('sys_https_host') . '/plugins/openidconnectclient/';
    }

    /**
     * @return array
     */
    public function process() {
        $user_info = parent::process();

        $this->invalidState();

        return $user_info;
    }

    private function invalidState() {
        $state_manager = $this->getStateManager();
        $state_manager->initState();
    }
}