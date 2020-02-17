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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\User\OAuth2\BearerTokenHeaderParser;
use Tuleap\User\OAuth2\OAuth2Exception;
use User_LoginException;
use User_LoginManager;

final class OAuth2ResourceServerMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenIdentifierTranslator
     */
    private $access_token_unserializer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AccessTokenVerifier
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
        $this->access_token_verifier     = \Mockery::mock(OAuth2AccessTokenVerifier::class);
        $this->login_manager             = \Mockery::mock(User_LoginManager::class);

        $this->middleware = new OAuth2ResourceServerMiddleware(
            HTTPFactoryBuilder::responseFactory(),
            new BearerTokenHeaderParser(),
            $this->access_token_unserializer,
            $this->access_token_verifier,
            $this->login_manager
        );
    }

    public function testAccessToTheResourceWhenTheTokenIsValid(): void
    {
        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            \Mockery::mock(SplitToken::class)
        );
        $this->access_token_verifier->shouldReceive('getUser')->andReturn(new \PFUser(['language_id' => 'en']));
        $this->login_manager->shouldReceive('validateAndSetCurrentUser');
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $handler->shouldReceive('handle')->andReturn($expected_response);

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertSame($expected_response, $response);
    }

    public function testAccessIsNotAllowedWhenTheAuthorizationHeaderIsNotCorrectlySet(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $response = $this->middleware->process(
            $this->buildServerRequest(''),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAccessIsNotAllowedWhenTheTokenCannotBeVerified(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            \Mockery::mock(SplitToken::class)
        );
        $this->access_token_verifier->shouldReceive('getUser')->andThrow(
            new class extends \RuntimeException implements OAuth2Exception {
            }
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
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
    }

    public function testAccessIsNotAllowedWhenTheUserCannotBeValidated(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $this->access_token_unserializer->shouldReceive('getSplitToken')->andReturn(
            \Mockery::mock(SplitToken::class)
        );
        $this->access_token_verifier->shouldReceive('getUser')->andReturn(new \PFUser(['language_id' => 'en']));
        $this->login_manager->shouldReceive('validateAndSetCurrentUser')->andThrow(
            new class extends User_LoginException {
            }
        );

        $response = $this->middleware->process(
            $this->buildServerRequest('Bearer FooToken'),
            $handler
        );

        $this->assertEquals(401, $response->getStatusCode());
    }


    private function buildServerRequest(string $authorization_header_line): ServerRequestInterface
    {
        $server_request = \Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getHeaderLine')->with('Authorization')->andReturn($authorization_header_line);

        return $server_request;
    }
}
