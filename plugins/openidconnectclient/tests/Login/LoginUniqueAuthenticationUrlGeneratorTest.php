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

require_once(__DIR__ . '/../bootstrap.php');

use TuleapTestCase;

class LoginUniqueAuthenticationUrlGeneratorTest extends TuleapTestCase
{
    public function skip()
    {
        $this->skipIf(PHP_VERSION_ID > 70000);
    }

    public function itGeneratesTheLoginUrlForTheProviderDefinedAsUniqueAuthenticationEndpoint()
    {
        $provider         = mock('Tuleap\OpenIDConnectClient\Provider\Provider');
        $provider_manager = mock('Tuleap\OpenIDConnectClient\Provider\ProviderManager');
        stub($provider_manager)->getProvidersUsableToLogIn()->returns(array($provider));
        $flow = mock('Tuleap\OpenIDConnectClient\Authentication\Flow');
        $flow->expectOnce('getAuthorizationRequestUri', array($provider, 'return_to'));

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $flow);
        $url_generator->getURL('return_to');
    }

    public function itExpectsAProviderDefinedAsUniqueAuthenticationEndpoint()
    {
        $provider_manager = mock('Tuleap\OpenIDConnectClient\Provider\ProviderManager');
        stub($provider_manager)->getProvidersUsableToLogIn()->returns(array());
        $flow = mock('Tuleap\OpenIDConnectClient\Authentication\Flow');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $flow);

        $this->expectException('Tuleap\OpenIDConnectClient\Login\IncoherentDataUniqueProviderException');
        $url_generator->getURL('');
    }


    public function itExpectsOnlyOneProviderDefinedAsUniqueAuthenticationEndpoint()
    {
        $provider_manager = mock('Tuleap\OpenIDConnectClient\Provider\ProviderManager');
        stub($provider_manager)->getProvidersUsableToLogIn()->returns(
            array(
                mock('Tuleap\OpenIDConnectClient\Provider\Provider'),
                mock('Tuleap\OpenIDConnectClient\Provider\Provider')
            )
        );
        $flow = mock('Tuleap\OpenIDConnectClient\Authentication\Flow');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $flow);

        $this->expectException('Tuleap\OpenIDConnectClient\Login\IncoherentDataUniqueProviderException');
        $url_generator->getURL('');
    }
}
