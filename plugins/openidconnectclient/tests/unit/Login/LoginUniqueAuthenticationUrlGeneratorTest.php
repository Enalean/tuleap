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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class LoginUniqueAuthenticationUrlGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTheLoginUrlIsGeneratedForTheProviderDefinedAsUniqueAuthenticationEndpoint(): void
    {
        $provider         = \Mockery::mock(Provider::class);
        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider_manager->shouldReceive('getProvidersUsableToLogIn')->andReturns([$provider]);
        $login_url_generator = \Mockery::mock(LoginURLGenerator::class);
        $login_url_generator->shouldReceive('getLoginURL')
            ->withArgs([$provider, 'return_to'])->andReturns('login_url');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $login_url_generator);
        $url_generator->getURL('return_to');
    }

    public function testAProviderDefinedAsUniqueAuthenticationEndpointIsExpected(): void
    {
        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider_manager->shouldReceive('getProvidersUsableToLogIn')->andReturns([]);
        $login_url_generator = new LoginURLGenerator('base_url');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $login_url_generator);

        $this->expectException(IncoherentDataUniqueProviderException::class);

        $url_generator->getURL('');
    }

    public function testOnlyOneProviderDefinedAsUniqueAuthenticationEndpointIsExpected(): void
    {
        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider_manager->shouldReceive('getProvidersUsableToLogIn')->andReturns([
            \Mockery::mock(Provider::class),
            \Mockery::mock(Provider::class),
        ]);
        $login_url_generator = new LoginURLGenerator('base_url');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $login_url_generator);

        $this->expectException(IncoherentDataUniqueProviderException::class);

        $url_generator->getURL('');
    }
}
