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

namespace Tuleap\OpenIDConnectClient\Authentication;

use InoOicClient\Oic\Authorization\Dispatcher;
use InoOicClient\Oic\Authorization\Request;
use Tuleap\OpenIDConnectClient\Authentication\Uri\Generator;
use Tuleap\OpenIDConnectClient\Provider\Provider;


class AuthorizationDispatcher extends Dispatcher {
    /**
     * @var Generator
     */
    private $uri_generator;

    public function __construct(StateManager $state_manager, Generator $uri_generator) {
        $this->setStateManager($state_manager);
        $this->uri_generator = $uri_generator;
    }

    public function createAuthorizationRequestUri(Request $request, Provider $provider, $return_to) {
        $state_manager = $this->getStateManager();
        $state         = $state_manager->initState($provider, $return_to);
        $request->setState($state->getSignedState());
        $request->setNonce($state->getNonce());

        $this->setLastRequest($request);
        return $this->uri_generator->createAuthorizationRequestUri($request);
    }
}