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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequest;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequestCreator;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class LoginUniqueAuthenticationUrlGeneratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTheLoginUrlIsGeneratedForTheProviderDefinedAsUniqueAuthenticationEndpoint()
    {
        $provider         = \Mockery::mock(Provider::class);
        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider_manager->shouldReceive('getProvidersUsableToLogIn')->andReturns([$provider]);
        $authorization_request_creator = \Mockery::mock(AuthorizationRequestCreator::class);
        $authorization_request_creator->shouldReceive('createAuthorizationRequest')
            ->withArgs([$provider, 'return_to'])->andReturns(\Mockery::spy(AuthorizationRequest::class));

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $authorization_request_creator);
        $url_generator->getURL('return_to');
    }

    public function testAProviderDefinedAsUniqueAuthenticationEndpointIsExpected()
    {
        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider_manager->shouldReceive('getProvidersUsableToLogIn')->andReturns([]);
        $authorization_request_creator = \Mockery::mock(AuthorizationRequestCreator::class);

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $authorization_request_creator);

        $this->expectException(IncoherentDataUniqueProviderException::class);

        $url_generator->getURL('');
    }

    public function testOnlyOneProviderDefinedAsUniqueAuthenticationEndpointIsExpected()
    {
        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider_manager->shouldReceive('getProvidersUsableToLogIn')->andReturns([
            \Mockery::mock(Provider::class),
            \Mockery::mock(Provider::class)
        ]);
        $authorization_request_creator = \Mockery::mock(AuthorizationRequestCreator::class);

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $authorization_request_creator);

        $this->expectException(IncoherentDataUniqueProviderException::class);

        $url_generator->getURL('');
    }
}
