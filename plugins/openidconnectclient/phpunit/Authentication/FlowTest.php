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

namespace Tuleap\OpenIDConnectClient\Authentication;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestSender;
use Tuleap\OpenIDConnectClient\Authentication\Uri\Generator;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class FlowTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        if (PHP_VERSION_ID > 70000) {
            $this->markTestSkipped('Not yet compatible with PHP 7');
        }
    }

    public function testValidAuthorizationRequestIsCreated()
    {
        $authorization_endpoint = 'https://endpoint.example.com';
        $signed_state           = 'Tuleap_signed_state';

        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider         = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getAuthorizationEndpoint')->andReturns($authorization_endpoint);
        $provider->shouldReceive('getClientId')->andReturns('1234');
        $provider->shouldReceive('getClientSecret')->andReturns('client_secret');
        $provider->shouldReceive('getTokenEndpoint')->andReturns('https://token.endpoint.example.com');
        $provider->shouldReceive('getUserInfoEndpoint')->andReturns('https://userinfo.endpoint.example.com');
        $provider->shouldReceive('isUniqueAuthenticationEndpoint')->andReturns(false);
        $state = \Mockery::mock(State::class);
        $state->shouldReceive('getSignedState')->andReturns($signed_state);
        $state->shouldReceive('getNonce')->andReturns('000000');
        $state_manager = \Mockery::mock(StateManager::class);
        $state_manager->shouldReceive('initState')->andReturns($state);
        $uri_generator    = new Generator();

        $flow = new Flow(
            $state_manager,
            new AuthorizationDispatcher($state_manager, $uri_generator),
            $provider_manager,
            \Mockery::spy(TokenRequestCreator::class),
            \Mockery::spy(TokenRequestSender::class),
            new IDTokenVerifier()
        );

        $request_uri = $flow->getAuthorizationRequestUri($provider, 'return_to');
        $this->assertStringStartsWith($authorization_endpoint, $request_uri);
        $this->assertNotContains($signed_state, $authorization_endpoint);
    }

    public function testValidAuthorizationRequestUriWithMultipleProvidersIsGenerated()
    {
        $authorization_endpoint  = 'https://endpoint.example.com';
        $authorization_endpoint2 = 'https://endpoint2.example.com';
        $signed_state            = 'Tuleap_signed_state';

        $provider_manager = \Mockery::mock(ProviderManager::class);
        $provider         = \Mockery::spy(Provider::class);
        $provider->shouldReceive('getAuthorizationEndpoint')->andReturns($authorization_endpoint);
        $provider->shouldReceive('getClientId')->andReturns('1234');
        $provider2 = \Mockery::spy(Provider::class);
        $provider2->shouldReceive('getAuthorizationEndpoint')->andReturns($authorization_endpoint2);
        $provider2->shouldReceive('getClientId')->andReturns('1234');
        $state = \Mockery::mock(State::class);
        $state->shouldReceive('getSignedState')->andReturns($signed_state);
        $state->shouldReceive('getNonce')->andReturns('000000');
        $state_manager = \Mockery::mock(StateManager::class);
        $state_manager->shouldReceive('initState')->andReturns($state);
        $uri_generator    = new Generator();

        $flow             = new Flow(
            $state_manager,
            new AuthorizationDispatcher($state_manager, $uri_generator),
            $provider_manager,
            \Mockery::spy(TokenRequestCreator::class),
            \Mockery::spy(TokenRequestSender::class),
            new IDTokenVerifier()
        );

        $request_uri  = $flow->getAuthorizationRequestUri($provider, 'return_to');
        $this->assertStringStartsWith($authorization_endpoint, $request_uri);
        $request_uri2 = $flow->getAuthorizationRequestUri($provider2, 'return_to');
        $this->assertStringStartsWith($authorization_endpoint2, $request_uri2);
    }
}
