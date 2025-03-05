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

use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EnableUniqueAuthenticationEndpointVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItAcceptsToEnableIfUserIsSuperUserAndLinkedToTheProvider(): void
    {
        $user_mapping_manager = $this->createMock(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $user_mapping_manager->method('getByProviderAndUser');
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->method('isUniqueAuthenticationEndpoint')->willReturn(false);
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertTrue($can_be_enabled);
    }

    public function testItCanNotBeEnabledByANonSuperUser(): void
    {
        $user_mapping_manager                           = $this->createMock(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $user                                           = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertFalse($can_be_enabled);
    }

    public function testItAcceptsToEnableAnAlreadyEnabledProvider(): void
    {
        $user_mapping_manager                           = $this->createMock(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->method('isUniqueAuthenticationEndpoint')->willReturn(true);
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertTrue($can_be_enabled);
    }

    public function testItRefusesToEnableIfUserIsLinkedToTheProvider(): void
    {
        $user_mapping_manager = $this->createMock(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager::class);
        $user_mapping_manager->method('getByProviderAndUser')->willThrowException(new UserMappingNotFoundException());
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $provider                                       = $this->createMock(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $provider->method('isUniqueAuthenticationEndpoint')->willReturn(false);
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);

        $can_be_enabled = $enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user);

        $this->assertFalse($can_be_enabled);
    }
}
