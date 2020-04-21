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
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\OAuth2Server\User\AuthorizationCreator;
use Tuleap\OAuth2Server\User\NewAuthorization;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationEndpointProcessConsentControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AuthorizationEndpointProcessConsentController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppFactory
     */
    private $app_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthenticationScopeBuilder
     */
    private $scope_builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationCreator
     */
    private $authorization_creator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationCodeResponseFactory
     */
    private $response_factory;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->user_manager          = M::mock(\UserManager::class);
        $this->app_factory           = M::mock(AppFactory::class);
        $this->scope_builder         = M::mock(AuthenticationScopeBuilder::class);
        $this->authorization_creator = M::mock(AuthorizationCreator::class);
        $this->response_factory      = M::mock(AuthorizationCodeResponseFactory::class);
        $this->csrf_token            = M::mock(\CSRFSynchronizerToken::class);
        $this->controller            = new AuthorizationEndpointProcessConsentController(
            $this->user_manager,
            $this->app_factory,
            $this->scope_builder,
            $this->authorization_creator,
            $this->response_factory,
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

    public function testHandleThrowsForbiddenWhenTheClientIdentifierCannotBeParsed(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'invalid_app_identifier', 'scope' => ['foo:bar', 'type:value']]
        );
        $this->csrf_token->shouldReceive('check');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenTheClientIdentifierIsUnknown(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $client_identifier = ClientIdentifier::fromClientId('tlp-client-id-404');
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => $client_identifier->toString(), 'scope' => ['foo:bar', 'type:value']]
        );
        $this->csrf_token->shouldReceive('check');
        $this->app_factory->shouldReceive('getAppMatchingClientId')->andThrow(new OAuth2AppNotFoundException($client_identifier));

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenNoValidScopeCanBeFound(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($user);
        $this->app_factory->shouldReceive('getAppMatchingClientId')->andReturn($this->buildOAuth2App(78));
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')
            ->andReturn(null);
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-78', 'scope' => ['not:found']]
        );
        $this->csrf_token->shouldReceive('check')->once();

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenCodeChallengeIsNotHexEncoded(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($user);
        $this->app_factory->shouldReceive('getAppMatchingClientId')->andReturn($this->buildOAuth2App(78));
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')
            ->andReturn(M::mock(AuthenticationScope::class));
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-78', 'scope' => ['foo:bar'], 'pkce_code_challenge' => 'not_hex_encoded']
        );
        $this->csrf_token->shouldReceive('check')->once();

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function dataProviderInvalidBodyParams(): array
    {
        return [
            'No redirect URI'                 => [['state' => 'xyz']],
            'Redirect URI is not a string'    => [['redirect_uri' => false]],
            'No App identifier'               => [['redirect_uri' => 'https://example.com']],
            'No scopes'                       => [['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-13']],
            'Scopes are not array of strings' => [['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-13', 'scope' => [false]]]
        ];
    }

    /**
     * @dataProvider dataProviderInvalidBodyParams
     */
    public function testHandleThrowsForbiddenWhenInvalidBodyParams(array $body_params): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $this->app_factory->shouldReceive('getAppMatchingClientId')->andReturn($this->buildOAuth2App(13));
        $request = (new NullServerRequest())->withParsedBody($body_params);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleRedirects(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($user);
        $this->app_factory->shouldReceive('getAppMatchingClientId')->andReturn($this->buildOAuth2App(77));
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')
            ->andReturn(M::mock(AuthenticationScope::class));
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-77', 'scope' => ['foo:bar', 'type:value', 'foo:bar']]
        );
        $this->csrf_token->shouldReceive('check')->once();
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createSuccessfulResponse')->once()->andReturn($response);
        $this->authorization_creator->shouldReceive('saveAuthorization')
            ->once()
            ->with(
                M::on(
                    function (NewAuthorization $new_authorization) use ($user) {
                        $identifiers = $new_authorization->getScopeIdentifiers();
                        return $new_authorization->getAppId() === 77
                            && $new_authorization->getUser() === $user
                            && count($identifiers) === 2
                            && $identifiers[0]->toString() === 'foo:bar'
                            && $identifiers[1]->toString() === 'type:value';
                    }
                )
            );

        $this->assertSame($response, $this->controller->handle($request));
    }

    private function buildOAuth2App(int $id): OAuth2App
    {
        return new OAuth2App($id, 'Name', 'https://example.com/redirect', true, new \Project(['group_id' => 102]));
    }
}
