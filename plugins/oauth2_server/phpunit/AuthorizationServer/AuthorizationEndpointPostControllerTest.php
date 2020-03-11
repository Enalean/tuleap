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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2Server\User\AuthorizationManager;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationEndpointPostControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AuthorizationEndpointPostController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AuthorizationCodeCreator
     */
    private $authorization_code_creator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationManager
     */
    private $authorization_manager;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->user_manager               = M::mock(\UserManager::class);
        $this->authorization_code_creator = \Mockery::mock(OAuth2AuthorizationCodeCreator::class);
        $this->authorization_manager      = M::mock(AuthorizationManager::class);
        $this->csrf_token                 = M::mock(\CSRFSynchronizerToken::class);
        $this->controller                 = new AuthorizationEndpointPostController(
            HTTPFactoryBuilder::responseFactory(),
            $this->user_manager,
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
            $this->authorization_code_creator,
            $this->authorization_manager,
            $this->csrf_token,
            M::mock(EmitterInterface::class)
        );
    }

    public function testHandleThrowsForbiddenWhenUserIsAnonymous(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );

        $this->expectException(ForbiddenException::class);
        $this->controller->handle(new NullServerRequest());
    }

    public function testHandleThrowsForbiddenWhenRequestHasNoBodyParams(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = new NullServerRequest();

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function dataProviderInvalidBodyParams(): array
    {
        return [
            'No redirect URI'              => [['state' => 'xyz']],
            'Redirect URI is not a string' => [['redirect_uri' => false]],
            'No App ID'                    => [['redirect_uri' => 'https://example.com']],
            'App ID cannot be cast to int' => [['redirect_uri' => 'https://example.com', 'app_id' => 'invalid']]
        ];
    }

    /**
     * @dataProvider dataProviderInvalidBodyParams
     */
    public function testHandleThrowsForbiddenWhenInvalidBodyParams(array $body_params): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody($body_params);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleRedirectsToRedirectURIWithAuthorizationCode(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($user);
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_id' => '77']
        );
        $this->csrf_token->shouldReceive('check')->once();
        $auth_code = 'auth_code_identifier';
        $this->authorization_code_creator->shouldReceive('createAuthorizationCodeIdentifier')->once()->andReturn(
            new ConcealedString($auth_code)
        );
        $this->authorization_manager->shouldReceive('saveAuthorization')
            ->once()
            ->with($user, 77);

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('code=' . $auth_code, $location);
    }

    public function testHandlePassesStateParameterWhenPresent(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($user);
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'state' => 'xyz', 'app_id' => '77']
        );
        $this->csrf_token->shouldReceive('check')->once();
        $auth_code = 'auth_code_identifier';
        $this->authorization_code_creator->shouldReceive('createAuthorizationCodeIdentifier')->once()->andReturn(
            new ConcealedString($auth_code)
        );
        $this->authorization_manager->shouldReceive('saveAuthorization')
            ->once()
            ->with($user, 77);

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('state=xyz', $location);
        $this->assertStringContainsString('code=' . $auth_code, $location);
    }
}
