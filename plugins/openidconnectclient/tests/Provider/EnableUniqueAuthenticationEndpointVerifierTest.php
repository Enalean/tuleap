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

namespace Tuleap\OpenIDConnectClient\Provider;

use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;
use TuleapTestCase;

require_once(__DIR__ . '/../bootstrap.php');

class EnableUniqueAuthenticationEndpointVerifierTest extends TuleapTestCase
{
    public function itAcceptsToEnableIfUserIsSuperUserAndLinkedToTheProvider()
    {
        $user_mapping_manager                           = mock('Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager');
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = mock('Tuleap\OpenIDConnectClient\Provider\Provider');
        stub($provider)->isUniqueAuthenticationEndpoint()->returns(false);
        $user                                           = mock('PFUser');
        stub($user)->isSuperUser()->returns(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertTrue($can_be_enabled);
    }

    public function itCanNotBeEnabledByANonSuperUser()
    {
        $user_mapping_manager                           = mock('Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager');
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = mock('Tuleap\OpenIDConnectClient\Provider\Provider');
        $user                                           = mock('PFUser');
        stub($user)->isSuperUser()->returns(false);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertFalse($can_be_enabled);
    }

    public function itAcceptsToEnableAnAlreadyEnabledProvider()
    {
        $user_mapping_manager                           = mock('Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager');
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = mock('Tuleap\OpenIDConnectClient\Provider\Provider');
        stub($provider)->isUniqueAuthenticationEndpoint()->returns(true);
        $user                                           = mock('PFUser');
        stub($user)->isSuperUser()->returns(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertTrue($can_be_enabled);
    }

    public function itRefusesToEnableIfUserIsLinkedToTheProvider()
    {
        $user_mapping_manager                           = mock('Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager');
        $user_mapping_manager->throwOn('getByProviderAndUser', new UserMappingNotFoundException());
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = mock('Tuleap\OpenIDConnectClient\Provider\Provider');
        stub($provider)->isUniqueAuthenticationEndpoint()->returns(false);
        $user                                           = mock('PFUser');
        stub($user)->isSuperUser()->returns(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertFalse($can_be_enabled);
    }
}
