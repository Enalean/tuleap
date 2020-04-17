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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Authentication\Authorization;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OpenIDConnectClient\Authentication\State;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class AuthorizationRequestCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SIGNED_STATE        = 'Tuleap_signed_state';
    private const NONCE_FOR_TEST      = '000000';
    private const PKCE_CODE_VERIFIER  = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const PKCE_CODE_CHALLENGE = 'ZtNPunH49FD35FWYhT5Tv8I7vRKQJ8uxMaL0_9eHjNA';

    private $state_manager;

    protected function setUp(): void
    {
        $state = \Mockery::mock(State::class);
        $state->shouldReceive('getSignedState')->andReturns(self::SIGNED_STATE);
        $state->shouldReceive('getNonce')->andReturns(self::NONCE_FOR_TEST);
        $state->shouldReceive('getPKCECodeVerifier')->andReturns(new ConcealedString(self::PKCE_CODE_VERIFIER));
        $this->state_manager = \Mockery::mock(StateManager::class);
        $this->state_manager->shouldReceive('initState')->andReturns($state);
    }

    public function testValidAuthorizationRequestIsCreated(): void
    {
        $authorization_endpoint = 'https://endpoint.example.com';

        $authorization_request_creator = new AuthorizationRequestCreator($this->state_manager);

        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getAuthorizationEndpoint')->andReturns($authorization_endpoint);
        $provider->shouldReceive('getClientId')->andReturns('1234');
        $provider->shouldReceive('getRedirectUri')->andReturns('https://exemple.com');
        $provider->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(true);

        $authorization_request = $authorization_request_creator->createAuthorizationRequest($provider, 'return_to');

        $authorization_request_url = $authorization_request->getURL();
        $this->assertStringStartsWith($authorization_endpoint, $authorization_request_url);
        $this->assertStringContainsString('client_id=1234', $authorization_request_url);
        $this->assertStringContainsString('redirect_uri=', $authorization_request_url);
        $this->assertStringContainsString('response_type=code', $authorization_request_url);
        $this->assertStringContainsString('scope=openid+profile+email', $authorization_request_url);
        $this->assertStringContainsString('state=' . self::SIGNED_STATE, $authorization_request_url);
        $this->assertStringContainsString('nonce=' . self::NONCE_FOR_TEST, $authorization_request_url);
        $this->assertStringContainsString('code_challenge_method=S256', $authorization_request_url);
        $this->assertStringContainsString('code_challenge=' . self::PKCE_CODE_CHALLENGE, $authorization_request_url);
    }

    public function testValidAuthorizationRequestWithMultipleProvidersIsGenerated(): void
    {
        $authorization_endpoint_1 = 'https://endpoint.example.com';
        $authorization_endpoint_2 = 'https://endpoint.example.com';

        $authorization_request_creator = new AuthorizationRequestCreator($this->state_manager);

        $provider_1 = \Mockery::mock(Provider::class);
        $provider_1->shouldReceive('getAuthorizationEndpoint')->andReturns($authorization_endpoint_1);
        $provider_1->shouldReceive('getClientId')->andReturns('1234');
        $provider_1->shouldReceive('getRedirectUri')->andReturns('https://exemple.com');
        $provider_1->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(false);

        $provider_2 = \Mockery::mock(Provider::class);
        $provider_2->shouldReceive('getAuthorizationEndpoint')->andReturns($authorization_endpoint_2);
        $provider_2->shouldReceive('getClientId')->andReturns('5678');
        $provider_2->shouldReceive('getRedirectUri')->andReturns('https://exemple.org');
        $provider_2->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(false);

        $authorization_request_1 = $authorization_request_creator->createAuthorizationRequest($provider_1, 'return_to');
        $this->assertStringStartsWith($authorization_endpoint_1, $authorization_request_1->getURL());

        $authorization_request_2 = $authorization_request_creator->createAuthorizationRequest($provider_2, 'return_to');
        $this->assertStringStartsWith($authorization_endpoint_2, $authorization_request_2->getURL());
    }
}
