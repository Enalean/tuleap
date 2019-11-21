<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Provider;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;

require_once(__DIR__ . '/../bootstrap.php');

class EnableUniqueAuthenticationEndpointVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItAcceptsToEnableIfUserIsSuperUserAndLinkedToTheProvider(): void
    {
        $user_mapping_manager                           = \Mockery::spy(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = \Mockery::spy(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(false);
        $user                                           = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturns(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertTrue($can_be_enabled);
    }

    public function testItCanNotBeEnabledByANonSuperUser(): void
    {
        $user_mapping_manager                           = \Mockery::spy(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = \Mockery::spy(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $user                                           = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturns(false);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertFalse($can_be_enabled);
    }

    public function testItAcceptsToEnableAnAlreadyEnabledProvider(): void
    {
        $user_mapping_manager                           = \Mockery::spy(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = \Mockery::spy(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(true);
        $user                                           = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturns(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertTrue($can_be_enabled);
    }

    public function testItRefusesToEnableIfUserIsLinkedToTheProvider(): void
    {
        $user_mapping_manager                           = \Mockery::spy(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $user_mapping_manager->shouldReceive('getByProviderAndUser')->andThrows(new UserMappingNotFoundException());
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = \Mockery::spy(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(false);
        $user                                           = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturns(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertFalse($can_be_enabled);
    }
}
