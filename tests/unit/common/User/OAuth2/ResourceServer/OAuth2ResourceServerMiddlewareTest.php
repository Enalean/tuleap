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

namespace Tuleap\User\OAuth2\ResourceServer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenDoesNotHaveRequiredScopeException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenExpiredException;
use Tuleap\User\OAuth2\BearerTokenHeaderParser;
use Tuleap\User\OAuth2\OAuth2Exception;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;
use User_LoginException;
use User_LoginManager;

final class OAuth2ResourceServerMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenIdentifierTranslator
     */
    private $access_token_unserializer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AuthenticationScope
     */
    private $required_scope;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&OAuth2AccessTokenVerifier
     */
    private $access_token_verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|User_LoginManager
     */
    private $login_manager;

    /**
     * @var OAuth2ResourceServerMiddleware
     */
    private $middleware;

    protected function setUp(): void
    {
        $this->access_token_unserializer = \Mockery::mock(SplitTokenIdentifierTranslator::class);
        $this->access_token_verifier     = $this->createStub(OAuth2AccessTokenVerifier::class);
        $this->required_scope            = \Mockery::mock(AuthenticationScope::class);
        $this->login_manager             = \Mockery::mock(User_LoginManager::class);

        $this->middleware = new OAuth2ResourceServerMiddleware(
            HTTPFactoryBuilder::responseFactory(),
            new BearerTokenHeaderParser(),
            $this->access_token_unserializer,
            $this->access_token_verifier,
            $this->required_scope,
            $this->login_manager
        );
    }

    public function testAccessToTheResourceWhenTheTokenIsValid(): void
    {
        $token = $this->createStub(SplitToken::class);
        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            $token
        );
        $granted_authorization = new GrantedAuthorization(UserTestBuilder::aUser()->build(), [$this->required_scope]);
        $this->access_token_verifier->method('getGrantedAuthorization')->willReturn($granted_authorization);
        $this->login_manager->shouldReceive('validateAndSetCurrentUser');
        $handler           = \Mockery::mock(RequestHandlerInterface::class);
        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $handler->shouldReceive('handle')->andReturn($expected_response);

        $request = $this->buildServerRequest('Bearer FooToken');
        $request->shouldReceive('withAttribute')
            ->once()
            ->with(OAuth2ResourceServerMiddleware::class, \Mockery::type(GrantedAuthorization::class))
            ->andReturnSelf();
        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expected_response, $response);
    }

    /**
     * @dataProvider dataProviderBadAuthorizationHeader
     */
    public function testAccessIsNotAllowedWhenTheAuthorizationHeaderIsNotCorrect(string $bad_authorization_header_for_bearer_token): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $response = $this->middleware->process(
            $this->buildServerRequest($bad_authorization_header_for_bearer_token),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Bearer realm="Tuleap OAuth2 Protected Resource"', $response->getHeaderLine('WWW-Authenticate'));
    }

    public function dataProviderBadAuthorizationHeader(): array
    {
        return [
            [''],
            ['NotABearer Foo'],
        ];
    }

    public function testAccessIsNotAllowedWhenTheTokenCannotBeVerified(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            \Mockery::mock(SplitToken::class)
        );
        $this->access_token_verifier->method('getGrantedAuthorization')->willThrowException(
            new class extends \RuntimeException implements OAuth2Exception {
            }
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            'Bearer realm="Tuleap OAuth2 Protected Resource" error="invalid_token"',
            $response->getHeaderLine('WWW-Authenticate')
        );
    }

    public function testAccessIsNotAllowedWhenTheTokenCannotBeParsed(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $this->access_token_unserializer->shouldReceive('getSplitToken')->andThrow(
            new class extends SplitTokenException
            {
            }
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer WrongToken'),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            'Bearer realm="Tuleap OAuth2 Protected Resource" error="invalid_token" error_description="Access token is malformed"',
            $response->getHeaderLine('WWW-Authenticate')
        );
    }

    public function testAccessIsNotAllowedWhenTheUserCannotBeValidated(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $access_token = $this->createStub(SplitToken::class);
        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            $access_token
        );
        $this->access_token_verifier->method('getGrantedAuthorization')->willReturn(
            new GrantedAuthorization(UserTestBuilder::aUser()->build(), [$this->required_scope])
        );
        $this->login_manager->shouldReceive('validateAndSetCurrentUser')->andThrow(
            new class extends User_LoginException {
            }
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            'Bearer realm="Tuleap OAuth2 Protected Resource" error="invalid_token" error_description="Cannot authenticate user"',
            $response->getHeaderLine('WWW-Authenticate')
        );
    }

    public function testAccessIsNotAllowedWhenTheGivenAccessTokenHasExpired(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $split_token = \Mockery::mock(SplitToken::class);
        $split_token->shouldReceive('getID')->andReturn(1);
        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            $split_token
        );

        $this->access_token_verifier->method('getGrantedAuthorization')->willThrowException(
            new OAuth2AccessTokenExpiredException($split_token)
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            'Bearer realm="Tuleap OAuth2 Protected Resource" error="invalid_token" error_description="Access token has expired"',
            $response->getHeaderLine('WWW-Authenticate')
        );
    }

    public function testAccessIsNotAllowedWhenTheGivenAccessTokenDoesNotHaveTheRequiredScope(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            \Mockery::mock(SplitToken::class)
        );
        $scope_identifier = OAuth2ScopeIdentifier::fromIdentifierKey('foo');
        $scope_definition = new /** @psalm-immutable */class implements AuthenticationScopeDefinition
        {
            public function getName(): string
            {
                return 'Foo';
            }

            public function getDescription(): string
            {
                return 'Foo Description';
            }
        };
        $this->required_scope->shouldReceive('getIdentifier')->andReturn($scope_identifier);
        $this->required_scope->shouldReceive('getDefinition')->andReturn($scope_definition);
        $this->access_token_verifier->method('getGrantedAuthorization')->willThrowException(
            new OAuth2AccessTokenDoesNotHaveRequiredScopeException($this->required_scope)
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(
            'Bearer realm="Tuleap OAuth2 Protected Resource" error="insufficient_scope" scope="foo"',
            $response->getHeaderLine('WWW-Authenticate')
        );
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|ServerRequestInterface
     */
    private function buildServerRequest(string $authorization_header_line)
    {
        $server_request = \Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getHeaderLine')->with('Authorization')->andReturn($authorization_header_line);

        return $server_request;
    }
}
