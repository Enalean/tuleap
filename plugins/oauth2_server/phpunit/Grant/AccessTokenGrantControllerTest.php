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
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2GrantAccessTokenFromAuthorizationCode;
use Tuleap\OAuth2Server\Grant\RefreshToken\OAuth2GrantAccessTokenFromRefreshToken;

final class AccessTokenGrantControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AccessTokenGrantController
     */
    private $controller;
    /**
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2GrantAccessTokenFromAuthorizationCode
     */
    private $grant_access_token_from_auth_code;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2GrantAccessTokenFromRefreshToken
     */
    private $grant_access_token_from_refresh_token;

    protected function setUp(): void
    {
        $this->grant_access_token_from_auth_code     = \Mockery::mock(OAuth2GrantAccessTokenFromAuthorizationCode::class);
        $this->grant_access_token_from_refresh_token = \Mockery::mock(OAuth2GrantAccessTokenFromRefreshToken::class);

        $this->response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $this->controller = new AccessTokenGrantController(
            new AccessTokenGrantErrorResponseBuilder($this->response_factory, $stream_factory),
            $this->grant_access_token_from_auth_code,
            $this->grant_access_token_from_refresh_token,
            \Mockery::mock(EmitterInterface::class)
        );
    }

    public function testSuccessfullyGrantAccessTokenWithAnAuthorizationCode(): void
    {
        $expected_response = $this->response_factory->createResponse();

        $this->grant_access_token_from_auth_code->shouldReceive('grantAccessToken')->once()->andReturn($expected_response);

        $request = \Mockery::mock(ServerRequestInterface::class);
        $app     = $this->buildOAuth2App();
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($app);
        $request->shouldReceive('getParsedBody')->andReturn(
            ['grant_type' => 'authorization_code']
        );

        $response = $this->controller->handle($request);
        $this->assertSame($expected_response, $response);
    }

    public function testSuccessfullyGrantAccessTokenWithRefreshToken(): void
    {
        $expected_response = $this->response_factory->createResponse();

        $this->grant_access_token_from_refresh_token->shouldReceive('grantAccessToken')->once()->andReturn($expected_response);

        $request = \Mockery::mock(ServerRequestInterface::class);
        $app     = $this->buildOAuth2App();
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($app);
        $request->shouldReceive('getParsedBody')->andReturn(
            ['grant_type' => 'refresh_token']
        );

        $response = $this->controller->handle($request);
        $this->assertSame($expected_response, $response);
    }

    public function testRejectsRequestThatDoesNotHaveAnExplicitGrantType(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn($this->buildOAuth2App());
        $request->shouldReceive('getParsedBody')->andReturn(null);

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

        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWhereTheClientHasNotBeenAuthenticated(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->andReturn(null);

        $response = $this->controller->handle($request);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('WWW-Authenticate'));
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_client"}', $response->getBody()->getContents());
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(1, 'name', 'https://example.com', true, \Mockery::mock(\Project::class));
    }
}
