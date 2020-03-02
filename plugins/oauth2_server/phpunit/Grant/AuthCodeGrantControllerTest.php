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

namespace Tuleap\OAuth2Server\Grant;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\OAuth2ServerException;

final class AuthCodeGrantControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AuthorizationCodeGrantResponseBuilder
     */
    private $response_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenIdentifierTranslator
     */
    private $auth_code_unserializer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AuthorizationCodeVerifier
     */
    private $auth_code_verifier;
    /**
     * @var AuthCodeGrantController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->response_builder       = \Mockery::mock(AuthorizationCodeGrantResponseBuilder::class);
        $this->auth_code_unserializer = \Mockery::mock(SplitTokenIdentifierTranslator::class);
        $this->auth_code_verifier     = \Mockery::mock(OAuth2AuthorizationCodeVerifier::class);

        $this->controller = new AuthCodeGrantController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->response_builder,
            $this->auth_code_unserializer,
            $this->auth_code_verifier,
            \Mockery::mock(EmitterInterface::class)
        );
    }

    public function testBuildsHTTPResponse(): void
    {
        $this->auth_code_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->auth_code_verifier->shouldReceive('getAuthorizationCode')->andReturn(
            OAuth2AuthorizationCode::approveForDemoScope(new \PFUser(['language_id' => 'en']))
        );
        $this->response_builder->shouldReceive('buildResponse')->andReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                new \DateTimeImmutable('@10')
            )
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $app     = $this->buildOAuth2App();
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($app);
        $request->shouldReceive('getParsedBody')->andReturn(
            [
                'grant_type'   => 'authorization_code',
                'code'         => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161',
                'redirect_uri' => $app->getRedirectEndpoint()
            ]
        );

        $response = $this->controller->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testRejectsRequestThatDoesNotHaveAnExplicitGrantType(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(null);

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithAnUnsupportedGrantType(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(['grant_type' => 'password']);

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWhereTheClientHasNotBeenAuthenticated(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn(null);

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('WWW-Authenticate'));
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_client"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithoutAnAuthCode(): void
    {
        $this->auth_code_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->auth_code_verifier->shouldReceive('getAuthorizationCode')->andReturn(
            OAuth2AuthorizationCode::approveForDemoScope(new \PFUser(['language_id' => 'en']))
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(
            ['grant_type' => 'authorization_code']
        );

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsWithANotValidAuthCode(): void
    {
        $this->auth_code_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->auth_code_verifier->shouldReceive('getAuthorizationCode')->andThrow(
            new class extends \RuntimeException implements OAuth2ServerException {
            }
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(
            ['grant_type' => 'authorization_code', 'code' => 'not_valid_auth_code']
        );

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithoutARedirectURI(): void
    {
        $this->auth_code_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->auth_code_verifier->shouldReceive('getAuthorizationCode')->andReturn(
            OAuth2AuthorizationCode::approveForDemoScope(new \PFUser(['language_id' => 'en']))
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(
            [
                'grant_type' => 'authorization_code',
                'code'       => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161'
            ]
        );

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestThatDoesNotTheExpectedRedirectURI(): void
    {
        $this->auth_code_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->auth_code_verifier->shouldReceive('getAuthorizationCode')->andReturn(
            OAuth2AuthorizationCode::approveForDemoScope(new \PFUser(['language_id' => 'en']))
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(
            [
                'grant_type'   => 'authorization_code',
                'code'         => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161',
                'redirect_uri' => 'https://evil.example.com'
            ]
        );

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(1, 'name', 'https://example.com', \Mockery::mock(\Project::class));
    }
}
