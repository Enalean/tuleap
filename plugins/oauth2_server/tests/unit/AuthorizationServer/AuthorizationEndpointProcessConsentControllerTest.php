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
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2ServerCore\App\AppFactory;
use Tuleap\OAuth2ServerCore\App\ClientIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\App\OAuth2AppNotFoundException;
use Tuleap\OAuth2Server\User\AuthorizationCreator;
use Tuleap\OAuth2Server\User\NewAuthorization;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationEndpointProcessConsentControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AuthorizationEndpointProcessConsentController
     */
    private $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppFactory
     */
    private $app_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthenticationScopeBuilder
     */
    private $scope_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationCreator
     */
    private $authorization_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationCodeResponseFactory
     */
    private $response_factory;
    /**
     * @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->user_manager          = $this->createMock(\UserManager::class);
        $this->app_factory           = $this->createMock(AppFactory::class);
        $this->scope_builder         = $this->createMock(AuthenticationScopeBuilder::class);
        $this->authorization_creator = $this->createMock(AuthorizationCreator::class);
        $this->response_factory      = $this->createMock(AuthorizationCodeResponseFactory::class);
        $this->csrf_token            = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller            = new AuthorizationEndpointProcessConsentController(
            $this->user_manager,
            $this->app_factory,
            $this->scope_builder,
            $this->authorization_creator,
            $this->response_factory,
            $this->csrf_token,
            $this->createMock(EmitterInterface::class)
        );
    }

    public function testHandleThrowsForbiddenWhenUserIsAnonymous(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );

        $this->expectException(ForbiddenException::class);
        $this->controller->handle(new NullServerRequest());
    }

    public function testHandleThrowsForbiddenWhenRequestHasNoBodyParams(): void
    {
        $this->user_manager->method('getCurrentUser')
            ->willReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = new NullServerRequest();

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenTheClientIdentifierCannotBeParsed(): void
    {
        $this->user_manager->method('getCurrentUser')
            ->willReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'invalid_app_identifier', 'scope' => ['foo:bar', 'type:value']]
        );
        $this->csrf_token->method('check');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenTheClientIdentifierIsUnknown(): void
    {
        $this->user_manager->method('getCurrentUser')
            ->willReturn(UserTestBuilder::aUser()->withId(102)->build());
        $client_identifier = ClientIdentifier::fromClientId('tlp-client-id-404');
        $request           = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => $client_identifier->toString(), 'scope' => ['foo:bar', 'type:value']]
        );
        $this->csrf_token->method('check');
        $this->app_factory->method('getAppMatchingClientId')->willThrowException(new OAuth2AppNotFoundException($client_identifier));

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenNoValidScopeCanBeFound(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')
            ->willReturn($user);
        $this->app_factory->method('getAppMatchingClientId')->willReturn($this->buildOAuth2App(78));
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturn(null);
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-78', 'scope' => ['not:found']]
        );
        $this->csrf_token->expects(self::once())->method('check');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenCodeChallengeIsNotHexEncoded(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')
            ->willReturn($user);
        $this->app_factory->method('getAppMatchingClientId')->willReturn($this->buildOAuth2App(78));
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturn($this->createMock(AuthenticationScope::class));
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-78', 'scope' => ['foo:bar'], 'pkce_code_challenge' => 'not_hex_encoded']
        );
        $this->csrf_token->expects(self::once())->method('check');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public static function dataProviderInvalidBodyParams(): array
    {
        return [
            'No redirect URI'                 => [['state' => 'xyz']],
            'Redirect URI is not a string'    => [['redirect_uri' => false]],
            'No App identifier'               => [['redirect_uri' => 'https://example.com']],
            'No scopes'                       => [['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-13']],
            'Scopes are not array of strings' => [['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-13', 'scope' => [false]]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidBodyParams')]
    public function testHandleThrowsForbiddenWhenInvalidBodyParams(array $body_params): void
    {
        $this->user_manager->method('getCurrentUser')
            ->willReturn(UserTestBuilder::aUser()->withId(102)->build());
        $this->app_factory->method('getAppMatchingClientId')->willReturn($this->buildOAuth2App(13));
        $request = (new NullServerRequest())->withParsedBody($body_params);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleRedirects(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')
            ->willReturn($user);
        $this->app_factory->method('getAppMatchingClientId')->willReturn($this->buildOAuth2App(77));
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturn($this->createMock(AuthenticationScope::class));
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_identifier' => 'tlp-client-id-77', 'scope' => ['foo:bar', 'type:value', 'foo:bar']]
        );
        $this->csrf_token->expects(self::once())->method('check');
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->expects(self::once())->method('createSuccessfulResponse')->willReturn($response);
        $this->authorization_creator->expects(self::once())->method('saveAuthorization')
            ->with(
                self::callback(
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

        self::assertSame($response, $this->controller->handle($request));
    }

    private function buildOAuth2App(int $id): OAuth2App
    {
        return new OAuth2App($id, 'Name', 'https://example.com/redirect', true, new \Project(['group_id' => 102]));
    }
}
