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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OpenIDConnectClient\Authentication\State;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class AuthorizationRequestCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SIGNED_STATE        = 'Tuleap_signed_state';
    private const NONCE_FOR_TEST      = '000000';
    private const PKCE_CODE_VERIFIER  = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const PKCE_CODE_CHALLENGE = 'ZtNPunH49FD35FWYhT5Tv8I7vRKQJ8uxMaL0_9eHjNA';

    private MockObject&StateManager $state_manager;

    protected function setUp(): void
    {
        $state = $this->createMock(State::class);
        $state->method('getSignedState')->willReturn(self::SIGNED_STATE);
        $state->method('getNonce')->willReturn(self::NONCE_FOR_TEST);
        $state->method('getPKCECodeVerifier')->willReturn(new ConcealedString(self::PKCE_CODE_VERIFIER));
        $this->state_manager = $this->createMock(StateManager::class);
        $this->state_manager->method('initState')->willReturn($state);
    }

    public function testValidAuthorizationRequestIsCreated(): void
    {
        $authorization_endpoint = 'https://endpoint.example.com';

        $authorization_request_creator = new AuthorizationRequestCreator($this->state_manager);

        $provider = $this->createMock(Provider::class);
        $provider->method('getAuthorizationEndpoint')->willReturn($authorization_endpoint);
        $provider->method('getClientId')->willReturn('1234');
        $provider->method('getRedirectUri')->willReturn('https://exemple.com');
        $provider->method('isUniqueAuthenticationEndpoint')->willReturn(true);

        $authorization_request = $authorization_request_creator->createAuthorizationRequest($provider, 'return_to');

        $authorization_request_url = $authorization_request->getURL();
        self::assertStringStartsWith($authorization_endpoint, $authorization_request_url);
        self::assertStringContainsString('client_id=1234', $authorization_request_url);
        self::assertStringContainsString('redirect_uri=', $authorization_request_url);
        self::assertStringContainsString('response_type=code', $authorization_request_url);
        self::assertStringContainsString('scope=openid+profile+email', $authorization_request_url);
        self::assertStringContainsString('state=' . self::SIGNED_STATE, $authorization_request_url);
        self::assertStringContainsString('nonce=' . self::NONCE_FOR_TEST, $authorization_request_url);
        self::assertStringContainsString('code_challenge_method=S256', $authorization_request_url);
        self::assertStringContainsString('code_challenge=' . self::PKCE_CODE_CHALLENGE, $authorization_request_url);
    }

    public function testValidAuthorizationRequestWithMultipleProvidersIsGenerated(): void
    {
        $authorization_endpoint_1 = 'https://endpoint.example.com';
        $authorization_endpoint_2 = 'https://endpoint.example.com';

        $authorization_request_creator = new AuthorizationRequestCreator($this->state_manager);

        $provider_1 = $this->createMock(Provider::class);
        $provider_1->method('getAuthorizationEndpoint')->willReturn($authorization_endpoint_1);
        $provider_1->method('getClientId')->willReturn('1234');
        $provider_1->method('getRedirectUri')->willReturn('https://exemple.com');
        $provider_1->method('isUniqueAuthenticationEndpoint')->willReturn(false);

        $provider_2 = $this->createMock(Provider::class);
        $provider_2->method('getAuthorizationEndpoint')->willReturn($authorization_endpoint_2);
        $provider_2->method('getClientId')->willReturn('5678');
        $provider_2->method('getRedirectUri')->willReturn('https://exemple.org');
        $provider_2->method('isUniqueAuthenticationEndpoint')->willReturn(false);

        $authorization_request_1 = $authorization_request_creator->createAuthorizationRequest($provider_1, 'return_to');
        self::assertStringStartsWith($authorization_endpoint_1, $authorization_request_1->getURL());

        $authorization_request_2 = $authorization_request_creator->createAuthorizationRequest($provider_2, 'return_to');
        self::assertStringStartsWith($authorization_endpoint_2, $authorization_request_2->getURL());
    }
}
