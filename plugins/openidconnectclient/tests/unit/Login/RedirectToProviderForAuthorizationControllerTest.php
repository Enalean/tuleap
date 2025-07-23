<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequest;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequestCreator;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RedirectToProviderForAuthorizationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RedirectToProviderForAuthorizationController $controller;
    private \PHPUnit\Framework\MockObject\MockObject&ProviderManager $provider_manager;
    private AuthorizationRequestCreator&\PHPUnit\Framework\MockObject\MockObject $authorization_request_creator;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider_manager              = $this->createMock(ProviderManager::class);
        $this->authorization_request_creator = $this->createMock(AuthorizationRequestCreator::class);
        $this->controller                    = new RedirectToProviderForAuthorizationController(
            HTTPFactoryBuilder::responseFactory(),
            $this->provider_manager,
            $this->authorization_request_creator,
            new SapiEmitter()
        );
    }

    public function testRedirectsToAuthorizationEndpoint(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('isUniqueAuthenticationEndpoint')->willReturn(false);

        $this->provider_manager->method('getById')->willReturn($provider);
        $this->provider_manager->method('isAProviderConfiguredAsUniqueAuthenticationEndpoint')->willReturn(false);

        $authorization_request = new AuthorizationRequest('https://endpoint.example.com/authorization');
        $this->authorization_request_creator->method('createAuthorizationRequest')->willReturn($authorization_request);

        $request  = (new NullServerRequest())->withQueryParams(['return_to' => '/my/'])->withAttribute('provider_id', '123');
        $response = $this->controller->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals($authorization_request->getURL(), $response->getHeaderLine('Location'));
    }

    public function testRejectsRequestWhenProviderDoesNotExist(): void
    {
        $this->provider_manager->method('getById')->willThrowException(new ProviderNotFoundException());

        $request = (new NullServerRequest())->withAttribute('provider_id', '404');

        $this->expectException(NotFoundException::class);
        $this->controller->handle($request);
    }

    public function testRejectsRequestWhenAskingToAuthenticateAgainstAnotherProviderThanTheOneSetInUniqueAuthenticationEndpointMode(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('isUniqueAuthenticationEndpoint')->willReturn(false);

        $this->provider_manager->method('getById')->willReturn($provider);
        $this->provider_manager->method('isAProviderConfiguredAsUniqueAuthenticationEndpoint')->willReturn(true);

        $request = (new NullServerRequest())->withAttribute('provider_id', '124');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }
}
