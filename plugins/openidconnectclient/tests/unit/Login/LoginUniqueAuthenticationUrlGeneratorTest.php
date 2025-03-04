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

use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LoginUniqueAuthenticationUrlGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testTheLoginUrlIsGeneratedForTheProviderDefinedAsUniqueAuthenticationEndpoint(): void
    {
        $provider         = $this->createMock(Provider::class);
        $provider_manager = $this->createMock(ProviderManager::class);
        $provider_manager->method('getProvidersUsableToLogIn')->willReturn([$provider]);
        $login_url_generator = $this->createMock(LoginURLGenerator::class);
        $login_url_generator->expects(self::atLeastOnce())->method('getLoginURL')
            ->with($provider, 'return_to')->willReturn('login_url');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $login_url_generator);
        $url_generator->getURL('return_to');
    }

    public function testAProviderDefinedAsUniqueAuthenticationEndpointIsExpected(): void
    {
        $provider_manager = $this->createMock(ProviderManager::class);
        $provider_manager->method('getProvidersUsableToLogIn')->willReturn([]);
        $login_url_generator = new LoginURLGenerator('base_url');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $login_url_generator);

        $this->expectException(IncoherentDataUniqueProviderException::class);

        $url_generator->getURL('');
    }

    public function testOnlyOneProviderDefinedAsUniqueAuthenticationEndpointIsExpected(): void
    {
        $provider_manager = $this->createMock(ProviderManager::class);
        $provider_manager->method('getProvidersUsableToLogIn')->willReturn([
            $this->createMock(Provider::class),
            $this->createMock(Provider::class),
        ]);
        $login_url_generator = new LoginURLGenerator('base_url');

        $url_generator = new LoginUniqueAuthenticationUrlGenerator($provider_manager, $login_url_generator);

        $this->expectException(IncoherentDataUniqueProviderException::class);

        $url_generator->getURL('');
    }
}
